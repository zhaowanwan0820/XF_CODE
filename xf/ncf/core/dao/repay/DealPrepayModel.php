<?php
namespace core\dao\repay;

use core\dao\deal\DealLoadModel;
use core\dao\deal\DealModel;
use core\dao\deal\OrderNotifyModel;
use core\dao\jobs\JobsModel;
use core\dao\thirdparty\ThirdpartyDkModel;
use core\enum\AccountEnum;
use core\enum\DealEnum;
use core\enum\DealDkEnum;
use core\enum\DealLoanRepayCalendarEnum;
use core\enum\DealLoanRepayEnum;
use core\enum\DealRepayEnum;
use core\enum\JobsEnum;
use core\enum\MsgbusEnum;
use core\enum\ThirdpartyDkEnum;
use core\enum\UserAccountEnum;
use core\enum\UserLoanRepayStatisticsEnum;
use core\service\account\AccountService;
use core\service\creditloan\CreditLoanService;
use core\service\deal\DealLoanRepayCalendarService;
use core\service\deal\DealService;
use core\service\msgbus\MsgbusService;
use core\service\thirdparty\ThirdpartyDkService;
use core\service\user\UserLoanRepayStatisticsService;
use core\service\user\UserService;
use libs\utils\Finance;
use libs\utils\Logger;
use core\dao\BaseModel;
use NCFGroup\Common\Library\Idworker;
use core\enum\PartialRepayEnum;

class   DealPrepayModel extends BaseModel {

    /**
     * 检查是否完成提前还款
     * @param $prepay_id
     * @return bool
     */
    private function _checkPrepayCompleted($prepay_id) {
        $prepay = DealPrepayModel::instance()->find($prepay_id);

        $params = array(":deal_id" => $prepay['deal_id']);
        // 投资笔数
        $deal_service = new DealService();
        if ($deal_service->isDealDTV3($prepay['deal_id']) === true) {
            $deal_load_cnt = 1;
        } else {
            $deal_load_cnt = DealLoadModel::instance()->count("`deal_id`=':deal_id'", $params);
        }

        // 提前还款本金笔数
        $deal_loan_repay_cnt = DealLoanRepayModel::instance()->count("`deal_id`=':deal_id' AND `type`='" . DealLoanRepayEnum::MONEY_PREPAY . "'", $params);

        // 相等则为还款完成
        if ($deal_loan_repay_cnt >= $deal_load_cnt) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 提前还款的收尾任务
     * @param int $prepay_id
     * @return bool
     */
    public function finishPrepay($param) {
        $prepay_id = $param['prepay_id'];
        $prepayUserId = intval($param['prepayUserId']);//提前还款用户ID
        $r = $this->_checkPrepayCompleted($prepay_id);
        if ($r === false) {
            throw new \Exception(JobsEnum::ERRORMSG_NEEDDELAY, JobsEnum::ERRORCODE_NEEDDELAY);
        }

        try {
            $this->db->startTrans();

            $prepay = DealPrepayModel::instance()->find($prepay_id);
            $deal = DealModel::instance()->find($prepay->deal_id);
            $deal->last_repay_time = $prepay->prepay_time;
            if ($deal->repayCompleted() === false) {
                throw new \Exception("update deal error");
            }


            $thirdPartyOrder = ThirdpartyDkService::getThirdPartyByOrderId($param['orderId']);
            if (!empty($thirdPartyOrder)) {
                $outerOrderRecord = ThirdpartyDkModel::instance()->find($thirdPartyOrder['id']);
                $outerOrderRecord->status = ThirdpartyDkEnum::REQUEST_STATUS_SUCCESS;
                $outerOrderRecord->update_time = time();
                $updateOrderRes = $outerOrderRecord->save();
                if (!$updateOrderRes) {
                    throw new \Exception("更新Dk状态失败");
                }
            }
            //接口异步回调通知
            if ($thirdPartyOrder['notify_url'] != '') {
                $orderNotifyInfo = OrderNotifyModel::instance()->findViaOrderId($thirdPartyOrder['client_id'], $thirdPartyOrder['order_id']);
                if (empty($orderNotifyInfo)) {
                    // 回调时，应该将outer_order_id和结果放到回调参数中的
                    $insertOrderNotifyData = [
                        'client_id'     => $thirdPartyOrder['client_id'],
                        'order_id'      => $thirdPartyOrder['order_id'],
                        'notify_url'    => $thirdPartyOrder['notify_url'],
                        'notify_params' => ['out_order_id'=>$thirdPartyOrder['outer_order_id'],'status'=>DealDkEnum::DK_STATUS_SUCC],
                    ];
                    $orderNotifyRes = OrderNotifyModel::instance()->insertData($insertOrderNotifyData);
                    if (!$orderNotifyRes) {
                        throw new \Exception("插入接口异步通知回调失败");
                    }
                }
            }

            $r = $deal->changeRepayStatus(DealEnum::DEAL_NOT_DURING_REPAY);
            if ($r === false) {
                throw new \Exception("update deal during status fail");
            }

            $r = DealRepayModel::instance()->cancelDealRepay($prepay->deal_id, $prepay->prepay_time);
            if ($r === false) {
                throw new \Exception("deal repay list empty");
            }

            $deal_service = new DealService();
            $isDT = $deal_service->isDealDT($deal['id']);

            $credit_loan_service = new CreditLoanService();
            if($credit_loan_service->isCreditingDeal($deal['id'])) {
                $jobs_model = new JobsModel();
                $jobs_model->priority = JobsEnum::DEAL_REPAY_CREDIT_LOAN;
                $param = array(
                    'deal_id' => $deal['id'],
                    'repay_type'=> 1 ,// 提前还款
                );
                $r = $jobs_model->addJob('\core\service\creditloan\CreditLoanService::dealCreditAfterRepay', $param);
                if ($r === false) {
                    throw new \Exception("Add CreditAfterRepay Jobs Fail");
                }
            }
            $mq_job_model = new JobsModel();
            $mq_param = array('prepayId'=>$prepay_id);
            $mq_job_model->priority = JobsEnum::PRIORITY_MESSAGE_QUEUE_PREPAY;
            $mq_res = $mq_job_model->addJob('\core\service\mq\MqService::prepay', array('param' => $mq_param), false, 90);
            if ($mq_res === false) {
                throw new \Exception("Add MqService prepay Jobs Fail");
            }
            $this->db->commit();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, "succ")));
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, "fail", $e->getMessage(), $e->getLine())));
            return false;
        }


        $message = array('dealId'=>$deal->id,'repayId'=>$prepay_id);
        MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_PREPAY_FINISH,$message);

        \libs\utils\Monitor::add('PH_DEAL_PREPAY');
        return true;
    }


    public function prepayDoing($auditId){
        $data = array('status' => DealRepayEnum::PREPAY_STATUS_REPAYED);
        return $this->updateBy($data,'id='.$this->id);
    }


    /**
     * 智多鑫三期底层资产提前还款
     */
    public function prepayDtV3($param) {
        $prepay_id = $param['prepay_id'];
        $prepay_user_id = $param['prepay_user_id'];
        $loan_user_id = app_conf('DT_YDT');

        $prepay = $this->find($prepay_id);
        $deal = DealModel::instance()->find($prepay->deal_id);
        $deal_service = new DealService();

        $drl = new DealLoanRepayModel();

        $syncRemoteData = array();

        try {
            $this->db->startTrans();

            $r = $this->db->query("UPDATE " . $drl->tableName() . " SET `status`='2' WHERE `deal_id`='{$prepay->deal_id}' AND `status`='0'");
            if ($r === false) {
                throw new Exception('update loan repay fail');
            }

            $deal_load = array(
                'id' => 0,
                'user_id' => $loan_user_id,
            );

            $this->savePrepayDealLoanRepay($deal, $prepay, $deal_load, $prepay->remain_principal, $prepay->prepay_interest, $prepay->prepay_compensation);

            // 投资用户回款
            $user = UserService::getUserById($loan_user_id);
            $accountId = AccountService::getUserAccountId($loan_user_id,UserAccountEnum::ACCOUNT_MANAGEMENT);
            if(!$accountId){
                throw new \Exception("未获取到账户ID userId:{$loan_user_id}");
            }
            $bizToken = array('dealId' => $deal['id'],'dealRepayId' => $prepay->id);
            if (!AccountService::changeMoney($accountId,$prepay->remain_principal, "提前还款本金", "编号".$deal['id'] . ' ' . $deal['name'],AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken)) {
                throw new \Exception('智多鑫回款失败-提前还款本金 userId:'.$loan_user_id);
            }
            if (!AccountService::changeMoney($accountId,$prepay->prepay_interest, "提前还款利息", "编号".$deal['id'] . ' ' . $deal['name'],AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken)) {
                throw new \Exception('智多鑫回款失败-提前还款利息 userId:'.$loan_user_id);
            }
            if (!AccountService::changeMoney($accountId,$prepay->prepay_compensation, "提前还款补偿金", "编号".$deal['id'] . ' ' . $deal['name'],AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken)) {
                throw new \Exception('智多鑫回款失败-提前还款补偿金 userId:'.$loan_user_id);
            }
            $this->db->commit();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, $deal_load['user_id'], "succ")));
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, $deal_load['user_id'], "fail", $e->getMessage(), $e->getLine())));
            throw $e;
        }

        return true;
    }

    /**
     * 根据每个投资id进行提前还款
     * @param int $deal_laon_id
     * @param int $prepay_id
     * @return bool
     */
    public function prepayByLoanId($param) {
        $deal_loan_id = $param['deal_loan_id'];
        $prepay_id = $param['prepay_id'];
        $prepay_user_id = $param['prepay_user_id'];
        $repay_type = $param['repay_type'];

        $dealService = new DealService();
        $prepay = $this->find($prepay_id);
        $deal_model = new DealModel();
        $deal = $deal_model->find($prepay->deal_id);
        $deal_load = DealLoadModel::instance()->find($deal_loan_id);
        $credit_loan_service = new CreditLoanService();
        $isNeedFreeze = $credit_loan_service->isNeedFreeze($deal,$deal_load['user_id'],$prepay_id,3);

        try {
            $this->db->startTrans();

            $money_cancel = DealLoanRepayModel::instance()->cancelDealLoanRepay($deal_loan_id);
            if ($money_cancel === false) {
                throw new \Exception("deal loan repay empty");
            }

            $deal_loan_repay_model = new DealLoanRepayModel();
            // 回款本金
            $principal = $deal_loan_repay_model->getTotalMoneyByTypeStatusLoanId($deal_loan_id,DealLoanRepayEnum::MONEY_PRINCIPAL,DealLoanRepayEnum::STATUS_NOTPAYED);
            // 年化收益率
            $rate = $deal['income_fee_rate'];

            // 提前还款利息
            $prepay_interest = Finance::prepay_money_intrest($principal, $prepay->remain_days, $rate);

            // 提前还款违约金  此处需要保留两位小数，因为数据库字段是保留两位小数，如果此处大于2位导致数据库四舍五入
            // 如：19.995 ，数据库在计算后当成 20 来处理
            $prepay_compensation = floorfix($deal_load['money'] * ($deal['prepay_rate']/100),2);

            // 实际还款总金额
           // $prepay_money = prepay_money($principal, $prepay->remain_days, $deal['loan_compensation_days'], $rate);
            $prepay_money = $principal + $prepay_interest + $prepay_compensation;

            // 中间值计算完成，将数据进行两位舍余
            $principal = floorfix($principal);
            $prepay_money = floorfix($prepay_money);
            $prepay_interest = floorfix($prepay_interest);
            //$prepay_compensation = $deal_model->floorfix($prepay_money - $prepay_interest - $principal);
            // 保存提前还款回款计划
            $this->savePrepayDealLoanRepay($deal, $prepay, $deal_load, $principal, $prepay_interest, $prepay_compensation);

            $deal_service = new DealService();
            $isDT = $deal_service->isDealDT($prepay->deal_id);
            $isDTV3 = $deal_service->isDealDTV3($prepay->deal_id);

            if ($isDT === true) {
                // 啥也不干
            } else {
                // 投资用户回款
                $tmpAccId = $isDTV3 ? UserAccountEnum::ACCOUNT_MANAGEMENT : UserAccountEnum::ACCOUNT_INVESTMENT;
                // 暂时屏蔽双账户
                //$accountId = AccountService::getUserAccountId($deal_load['user_id'],$tmpAccId);
                $accountId = $deal_load['user_id'];
                if(!$accountId){
                    throw new \Exception("未获取到账户ID userId:{$deal_load['user_id']}");
                }

                $bizToken = array('dealId' => $deal['id'],'dealRepayId' => $prepay_id,'dealLoadId' => $deal_loan_id);
                if ($repay_type == \core\enum\DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH) {
                    $this->addDZHRepayMoneyLog($prepay_id, $deal_loan_id, "提前还款本金", "编号".$deal['id'].' '.$deal['name'],$accountId,PartialRepayEnum::FEE_TYPE_PRINCIPAL, $bizToken);
                } else {
                    if (!AccountService::changeMoney($accountId,$principal, "提前还款本金", "编号".$deal['id'] . ' ' . $deal['name'],AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken)) {
                        throw new \Exception('智多鑫回款失败-提前还款本金 userId:'.$deal_load['user_id']);
                    }
                }


                if($credit_loan_service->isCreditingUser($deal_load['user_id'],$deal['id'])){
                    if (!AccountService::changeMoney($accountId,$principal, "贷款冻结", '冻结 "' . $deal['name'] .'" 投资本金',AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken)) {
                        throw new \Exception('贷款冻结失败 userId:'.$deal_load['user_id']);
                    }
                }elseif($isNeedFreeze === true){
                    /** 如果用户发生过借款 冻结用户本金  $credit_loan_service */
                    if (!AccountService::changeMoney($accountId,$principal, "网信速贷还款冻结", '冻结 "' . $deal['name'] .'" 投资本金',AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken)) {
                        throw new \Exception('网信速贷还款冻结失败 userId:'.$deal_load['user_id']);
                    }
                    $credit_loan_service->freezeNotifyCreditloan($deal_load['user_id'],$deal['id'],$prepay_id,3);
                }

                $money_info[UserLoanRepayStatisticsEnum::CG_NOREPAY_PRINCIPAL] = -$principal;
                $money_info[UserLoanRepayStatisticsEnum::CG_NOREPAY_EARNINGS] = -$money_cancel;
                $money_info[UserLoanRepayStatisticsEnum::CG_TOTAL_EARNINGS] = $prepay_interest;


                // 处理回款日历
                $calInfo[DealLoanRepayCalendarEnum::PREPAY_PRINCIPAL] = $principal;
                $calInfo[DealLoanRepayCalendarEnum::PREPAY_INTEREST] = $prepay_interest;

                if (bccomp($prepay_compensation, '0.00', 2) > 0) {
                    $money_info[UserLoanRepayStatisticsEnum::LOAD_TQ_IMPOSE] = $prepay_compensation;
                    $calInfo[DealLoanRepayCalendarEnum::PREPAY_INTEREST] +=$prepay_compensation;
                }

                if ($repay_type == \core\enum\DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH) {
                    $this->addDZHRepayMoneyLog($prepay_id, $deal_loan_id, "提前还款利息", "编号".$deal['id'].' '.$deal['name'],$accountId,PartialRepayEnum::FEE_TYPE_INTEREST, $bizToken);
                } else {
                    if (!AccountService::changeMoney($accountId,$prepay_interest, "提前还款利息", '编号' . $deal['id'] . ' ' . $deal['name'],AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken)) {
                        throw new \Exception('提前还款利息失败 userId:'.$deal_load['user_id']);
                    }
                }
                if ($repay_type == \core\enum\DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH) {
                    $this->addDZHRepayMoneyLog($prepay_id, $deal_loan_id, "提前还款补偿金", "编号".$deal['id'].' '.$deal['name'],$accountId,PartialRepayEnum::FEE_TYPE_COMPEN, $bizToken);
                } else {
                    if (!AccountService::changeMoney($accountId,$prepay_compensation, "提前还款补偿金", '编号' . $deal['id'] . ' ' . $deal['name'],AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken)) {
                        throw new \Exception('提前还款补偿金失败 userId:'.$deal_load['user_id']);
                    }
                }

                // 处理资产总额
                //$money_info[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] = $principal;
                $money_info[UserLoanRepayStatisticsEnum::NOREPAY_PRINCIPAL] = -$principal;
                $money_info[UserLoanRepayStatisticsEnum::LOAD_EARNINGS] = $prepay_interest;

                $money_info[UserLoanRepayStatisticsEnum::NOREPAY_INTEREST] = -$money_cancel;

                if (UserLoanRepayStatisticsService::updateUserAssets($deal_load['user_id'], $money_info) === false) {
                    throw new \Exception("user loan repay statistics error");
                }

                if (DealLoanRepayCalendarService::collect($deal_load['user_id'], time(), $calInfo) === false) {
                    throw new \Exception("save calendar error");
                }
            }

            // TODO 判断是否使用了加息券

            $this->db->commit();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, $deal_loan_id, $deal_load['user_id'], "succ")));
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, $deal_loan_id, $deal_load['user_id'], "fail", $e->getMessage(), $e->getLine())));
            throw $e;
        }
        return true;
    }

    public function addDZHRepayMoneyLog($prepayId,$dealLoanId,$logType,$note,$accountId,$feeTypes, $bizToken = array()) {


        $partialRepayModel = new \core\dao\repay\PartialRepayModel();
        //支付借款人的钱
        $borrowerRepayMoney = $partialRepayModel->getPrepayMoneyByLoanId($prepayId,$dealLoanId,PartialRepayEnum::REPAY_TYPE_BORROWER,$feeTypes);
        if(bccomp($borrowerRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            if (!AccountService::changeMoney($accountId, $borrowerRepayMoney, $logType, $note,AccountEnum::MONEY_TYPE_INCR, false,true, 0, $bizToken)) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }

        //支付代充值机构的钱
        $rechargeRepayMoney = $partialRepayModel->getPrepayMoneyByLoanId($prepayId, $dealLoanId, PartialRepayEnum::REPAY_TYPE_COMPENSATORY, $feeTypes);
        if(bccomp($rechargeRepayMoney,'0.00',2) == 1) { //代偿还款大于0
            if (!AccountService::changeMoney($accountId, $rechargeRepayMoney, $logType, $note,AccountEnum::MONEY_TYPE_INCR, false,true, 0, $bizToken)) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }
        return true;
    }

    /**
     * 生成提前还款的回款计划
     */
    public function savePrepayDealLoanRepay($deal, $deal_repay, $deal_loan, $prepay_principal, $prepay_interest, $prepay_compensation) {
        $dlr_model = new DealLoanRepayModel();

        $dlr_model->deal_id = $deal['id'];
        $dlr_model->deal_repay_id = $deal_repay['id'];
        $dlr_model->deal_loan_id = $deal_loan['id'];
        $dlr_model->loan_user_id = $deal_loan['user_id'];
        $dlr_model->borrow_user_id = $deal['user_id'];
        $dlr_model->status = DealLoanRepayEnum::STATUS_ISPAYED;
        $dlr_model->time = to_timespan(to_date(get_gmtime(),'Y-m-d'));
        $dlr_model->real_time = to_timespan(to_date(get_gmtime(),'Y-m-d'));
        $dlr_model->create_time = get_gmtime();
        $dlr_model->update_time = get_gmtime();
        $dlr_model->deal_type = $deal_repay['deal_type'];

        // 本金
        $dlr_model->type = DealLoanRepayEnum::MONEY_PREPAY;
        $dlr_model->money = $prepay_principal;
        if ($dlr_model->insert() === false) {
            throw new \Exception("deal_loan_repay insert fail");
        }

        // 利息
        $dlr_model->type = DealLoanRepayEnum::MONEY_PREPAY_INTREST;
        $dlr_model->money = $prepay_interest;
        if ($dlr_model->insert() === false) {
            throw new \Exception("deal_loan_repay insert fail");
        }

        // 补偿金
        $dlr_model->type = DealLoanRepayEnum::MONEY_COMPENSATION;
        $dlr_model->money = $prepay_compensation;
        if (bccomp($dlr_model->money, 0, 2) == 1) {
            if ($dlr_model->insert() === false) {
                throw new \Exception("deal_loan_repay insert fail");
            }
        }

        return true;
    }


   /**
    * 获取标的所有提前还款的标
    */
    public function getPrepaysByTime($start,$end){
        // 数据库晚了8小时，查询出来的时间需要＋8小时
        // 当前时间需要－8小时获取今天2点的时间戳,今天开始时间减去8小时
        $condition = sprintf('`status`=1 AND prepay_time >= %s AND prepay_time<%s',$start,$end);
        $ret = $this->findAllViaSlave($condition, false, 'COUNT(DISTINCT(deal_id)) AS deal_count');
        if (is_array($ret) && count($ret) > 0) {
            return $ret;
        } else {
            return array();
        }
    }
    /**
     *根据审核状态统计标的利息
     * @string $deal_types 标的类型
     */
    public function getPrepayDealInterestByStatus($status = 1 ,$deal_types = '') {
        $status = intval($status);
        $deal_type_cond = '';
        if(!empty($deal_types)) {
            $deal_type_cond = ' AND deal_type IN ('. $deal_types .') ';
        }

        $sql = sprintf("SELECT SUM(`prepay_interest`+`prepay_compensation`) as `sum` FROM %s WHERE `status` = '%d' %s ",$this->tableName(),$status,$deal_type_cond);
        $result = $this->findAllBySqlViaSlave($sql,true);
        return $result['0']['sum'];
    }

    /**
     * 提前还款，根据投资人投资记录计算借款人实际还款金额
     * @param int $deal_id
     * @param floot $remain_principal 剩余本金
     * @param int $remain_days 剩余天数
     * @param array $result 投资人实际获得的金额总和，即借款人实际还款金额
     */
    public function getPrepayMoney($deal_id, $remain_principal, $remain_days) {
        $deal = DealModel::instance()->find($deal_id);
        $rate = $deal['income_fee_rate'];
        $result = array(
            'prepay_money' => 0,
            'principal' => 0,
            'prepay_interest' => 0,
            'loan_fee' => 0,
            'consult_fee' => 0,
            'guarantee_fee' => 0,
            'pay_fee' => 0,
        );

        $deal_service = new DealService();
        if ($deal_service->isDealDTV3($deal_id) === true) {
            $principal = $remain_principal;
            $prepay_interest = floorfix(Finance::prepay_money_intrest($principal, $remain_days, $rate));
            $prepay_compensation = floorfix($deal['borrow_amount'] * ($deal['prepay_rate']/100));
            $prepay_money = $principal + $prepay_interest + $prepay_compensation;
            $result['prepay_money'] = bcadd($result['prepay_money'],floorfix($prepay_money),2);
            $result['principal'] = bcadd($result['principal'],floorfix($principal),2);
            $result['prepay_interest'] = bcadd($result['prepay_interest'],floorfix($prepay_interest),2);
        } else {
            // 投资记录
            $deal_loan_model = new DealLoadModel();
            $deal_loan_list = $deal_loan_model->getDealLoanList($deal_id);

            $deal_loan_repay_model = new DealLoanRepayModel();
            foreach ($deal_loan_list as $deal_loan) {
                // 回款本金
                //$principal = $deal_loan['money'] * $remain_principal / $deal['borrow_amount'];
                $principal = $deal_loan_repay_model->getTotalMoneyByTypeStatusLoanId($deal_loan['id'],DealLoanRepayEnum::MONEY_PRINCIPAL,DealLoanRepayEnum::STATUS_NOTPAYED);
                // 回款利息
                $prepay_interest = floorfix(Finance::prepay_money_intrest($principal, $remain_days, $rate));

                // 提前还款违约金
                $prepay_compensation = floorfix($deal_loan['money'] * ($deal['prepay_rate']/100));
                // 回款实际金额
                //$prepay_money = prepay_money($deal_loan['money'],$principal, $remain_days, $deal['loan_compensation_days'], $rate);
                $prepay_money = $principal + $prepay_interest + $prepay_compensation;
                // 进行舍余后，计算实际回款总额

                $result['prepay_money'] = bcadd($result['prepay_money'],floorfix($prepay_money),2);
                $result['principal'] = bcadd($result['principal'],floorfix($principal),2);
                $result['prepay_interest'] = bcadd($result['prepay_interest'],floorfix($prepay_interest),2);
            }
        }

        $deal_repay_model = new \core\dao\repay\DealRepayModel();
        $deal_repay_list = $deal_repay_model->getDealUnpaiedRepayListByDealId($deal_id);
        foreach ($deal_repay_list as $k => $v) {
            $result['loan_fee'] += $v['loan_fee'];
            $result['consult_fee'] += $v['consult_fee'];
            $result['guarantee_fee'] += $v['guarantee_fee'];
            $result['pay_fee'] += $v['pay_fee'];
        }

        return $result;
    }
}
