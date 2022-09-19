<?php
/**
 * DealPrepayModel.php
 * @author wangyiming<wangyiming@ucfgroup.com>
 */

namespace core\dao;

use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\DealLoadModel;
use core\dao\DealLoanRepayModel;
use core\dao\FinanceQueueModel;
use core\dao\JobsModel;

use core\service\CreditLoanService;
use core\service\DealService;
use core\service\UserService;
use core\service\jifu\JfTransferService;
use libs\sms\SmsServer;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\UserLoanRepayStatisticsService;
use core\service\DealLoanRepayCalendarService;
use core\service\oto\O2ODiscountRateService;
use core\service\MsgBoxService;

use core\event\DealRepayMsgEvent;
use core\event\DealLoanPrepayMsgEvent;
use core\event\ReserveDealRepayCacheEvent;

use libs\utils\Logger;

\FP::import("libs.libs.msgcenter");
\FP::import("app.deal");

/**
 * 提前还款类
 *
 * Class DealPrepayModel
 */
class DealPrepayModel extends BaseModel {

    const PREPAY_LOCK_KEY_PREFIX = "DEAL_PREPAY_";

    /**
     * 提前还款的收尾任务
     * @param int $prepay_id
     * @return bool
     */
    public function finishPrepay($param) {
        $prepay_id = $param['prepay_id'];
        $prepayUserId = intval($param['prepayUserId']);//提前还款用户ID
        $isBorrowerSelf = $param['isBorrowerSelf'] ? true : false;
        $r = $this->_checkPrepayCompleted($prepay_id);
        if ($r === false) {
            throw new \Exception(JobsModel::ERRORMSG_NEEDDELAY, JobsModel::ERRORCODE_NEEDDELAY);
        }

        try {
            $this->db->startTrans();

            $prepay = DealPrepayModel::instance()->find($prepay_id);
            $deal = DealModel::instance()->find($prepay->deal_id);
            $deal->last_repay_time = $prepay->prepay_time;
            if ($deal->repayCompleted() === false) {
                throw new \Exception("update deal error");
            }

            $thirdPartyOrder = \core\service\ThirdpartyDkService::getThirdPartyByOrderId($param['orderId']);
            if (!empty($thirdPartyOrder)) {
                $outerOrderRecord = \core\dao\ThirdpartyDkModel::instance()->find($thirdPartyOrder['id']);
                $outerOrderRecord->status = \core\dao\ThirdpartyDkModel::REQUEST_STATUS_SUCCESS;
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
                    $insertOrderNotifyData = [
                        'client_id'     => $thirdPartyOrder['client_id'],
                        'order_id'      => $thirdPartyOrder['order_id'],
                        'notify_url'    => $thirdPartyOrder['notify_url'],
                        'notify_params' => $thirdPartyOrder['params']
                    ];
                    $orderNotifyRes = OrderNotifyModel::instance()->insertData($insertOrderNotifyData);
                    if (!$orderNotifyRes) {
                        throw new \Exception("插入接口异步通知回调失败");
                    }
                }
            }

            $r = $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
            if ($r === false) {
                throw new \Exception("update deal during status fail");
            }

            $r = DealRepayModel::instance()->cancelDealRepay($prepay->deal_id, $prepay->prepay_time);
            if ($r === false) {
                throw new \Exception("deal repay list empty");
            }

            $deal_service = new \core\service\DealService();
            $isDT = $deal_service->isDealDT($deal['id']);

            $credit_loan_service = new CreditLoanService();
            if($credit_loan_service->isCreditingDeal($deal['id'])) {
                $jobs_model = new JobsModel();
                $jobs_model->priority = 100;
                $param = array(
                    'deal_id' => $deal['id'],
                    'repay_type'=> 1 ,// 提前还款
                );
                $r = $jobs_model->addJob('\core\service\CreditLoanService::dealCreditAfterRepay', $param);
                if ($r === false) {
                    throw new \Exception("Add CreditAfterRepay Jobs Fail");
                }
            }

            $mq_job_model = new JobsModel();
            $mq_param = array('prepayId'=>$prepay_id);
            $mq_job_model->priority = JobsModel::PRIORITY_MESSAGE_QUEUE_PREPAY;
            $mq_res = $mq_job_model->addJob('\core\service\mq\MqService::prepay', array('param' => $mq_param), false, 90);
            if ($mq_res === false) {
                throw new \Exception("Add MqService prepay Jobs Fail");
            }

            $this->db->commit();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, 'finishPrepay', "succ")));
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, "fail", $e->getMessage(), $e->getLine())));
            return false;
        }

        // 判断是否将回款记录同步到即付宝
        $jobs_model = new JobsModel();
        $param = array(
            'deal_id' => $prepay->deal_id,
            'prepay_id' => $prepay_id
        );
        $r = $jobs_model->addJob('\core\service\jifu\JfLoanRepayService::syncPrepayToJf', $param);
        $jobs_model->priority = 84;
        if ($r === false) {
            throw new \Exception("Add Jobs Fail");
        }

        $deal_service = new DealService();
        $is_dtv3 = $deal_service->isDealDTV3($deal['id']);
        if ( !($isDT === true || $is_dtv3 === true) ) {
            // JIRA#3102 回款短信整合 PM:lipanpan
            $obj = new GTaskService();
            $event = new DealRepayMsgEvent($prepay_id, $prepay->deal_id);
            $obj->doBackground($event, 1);

            // 站内信
            $obj_prepay = new GTaskService();
            $event_prepay = new DealLoanPrepayMsgEvent($prepay_id);
            $obj_prepay->doBackground($event_prepay, 1);

            //记录随鑫约回款缓存
            $obj_reserve = new GTaskService();
            $event_reserve = new ReserveDealRepayCacheEvent($prepay_id, $prepay->deal_id);
            $obj_reserve->doBackground($event_reserve, 1);
        }

        if($deal_service->isDealYtsh($prepay->deal_id)){
            $XHService = new \core\service\XHService();
            $XHService->repaySuccessNotify($prepay->deal_id,$prepay->id,\core\service\XHService::REPAY_TYPE_PREPAY);
        }


        $user = UserModel::instance()->find($prepay['user_id']);
        $content = "尊敬的客户，“" . $deal['name'] . "”的提前还款申请已通过审核，本次借款已全部还清。";
        send_user_msg("", $content, 0, $prepay['user_id'], get_gmtime(), 0, 1, 8);

        if($isBorrowerSelf) {
            $userService = new UserService();
            $userInfo = $userService->getUserViaSlave($prepay->user_id);

            $ds = new \core\service\DealService();
            $relateUserId = $ds->getDealConsultRelateUserId($prepay->deal_id);
            $content = "借款方/转让方".$userInfo->real_name."于 ".date('Y-m-d H:i:s')." 对其在网信的借款 “" . $deal['name'] . "” 发起提前还款，总额".$prepay->prepay_money."元。";
            if($relateUserId) {
                $msgbox = new MsgBoxService();
                $msgbox->create($relateUserId, 40, "借款人提前还款", $content);
            }
        }

        $arr = array(
            'name' => $deal['name'],
        );
        // SMSSend 提前还款审核通过短信 , 暂不支持企业用户
        \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_SMS_PREPAY_PASS', $arr, $prepay['user_id']);

        \libs\utils\Monitor::add('DEAL_PREPAY');

        \SiteApp::init()->dataCache->getRedisInstance()->del(self::PREPAY_LOCK_KEY_PREFIX . $deal['id']);
        return true;
    }

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
        $deal_loan_repay_cnt = DealLoanRepayModel::instance()->count("`deal_id`=':deal_id' AND `type`='" . DealLoanRepayModel::MONEY_PREPAY . "'", $params);

        // 相等则为还款完成
        if ($deal_loan_repay_cnt >= $deal_load_cnt) {
            return true;
        } else {
            return false;
        }
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

        $isP2pPath = $deal_service->isP2pPath($deal);
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

            $bizToken = [
                'dealId' => $deal['id'],
            ];
            // 投资用户回款
            $user = UserModel::instance()->find($loan_user_id);
            $user->changeMoneyDealType = $deal_service->getDealType($deal);
            $user->changeMoney($prepay->remain_principal, '提前还款本金', '编号' . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);

            $user->changeMoney($prepay->prepay_interest, '提前还款利息', '编号' . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);
            $user->changeMoney($prepay->prepay_compensation, '提前还款补偿金', '编号' . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);

            // 资管转账
            if (bccomp($prepay->remain_principal, '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'PREPAYDEAL|' . $prepay['id'],
                    'payerId' => $prepay_user_id,
                    'receiverId' => $loan_user_id,
                    'repaymentAmount' => bcmul($prepay->remain_principal, 100),
                    'curType' => 'CNY',
                    'bizType' => 1,
                    'batchId' => $deal['id'],
                );
            }

            if (bccomp(bcadd($prepay->prepay_compensation, $prepay->prepay_interest, 2) ,'0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'PREPAYINTEREST|' . $prepay['id'],
                    'payerId' => $prepay_user_id,
                    'receiverId' => $loan_user_id,
                    'repaymentAmount' => bcmul(bcadd($prepay->prepay_compensation, $prepay->prepay_interest, 4), 100),
                    'curType' => 'CNY',
                    'bizType' => 1,
                    'batchId' => $deal['id'],
                );
            }

            if(!$isP2pPath){
                if (!FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH)) {
                    throw new \Exception("FinanceQueueModel push error");
                }
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

            $isDealExchange = ($deal['deal_type'] == DealModel::DEAL_TYPE_EXCHANGE) ? true : false;//是不是大金所标的
            $isDealCG = $dealService->isP2pPath($deal);

            $dealType = $dealService->getDealType($deal);

            $deal_loan_repay_model = new DealLoanRepayModel();
            // 回款本金
            //$principal = $deal_load['money'] * ($prepay->remain_principal / $deal['borrow_amount']);
            $principal = $deal_loan_repay_model->getTotalMoneyByTypeStatusLoanId($deal_loan_id,DealLoanRepayModel::MONEY_PRINCIPAL,DealLoanRepayModel::STATUS_NOTPAYED);
            // 年化收益率
            $rate = $deal['income_fee_rate'];

            // 提前还款利息
            $prepay_interest = prepay_money_intrest($principal, $prepay->remain_days, $rate);

            // 提前还款违约金  此处需要保留两位小数，因为数据库字段是保留两位小数，如果此处大于2位导致数据库四舍五入
            // 如：19.995 ，数据库在计算后当成 20 来处理
            $prepay_compensation = $deal_model->floorfix($deal_load['money'] * ($deal['prepay_rate']/100),2);

            // 实际还款总金额
           // $prepay_money = prepay_money($principal, $prepay->remain_days, $deal['loan_compensation_days'], $rate);
            $prepay_money = $principal + $prepay_interest + $prepay_compensation;

            // 中间值计算完成，将数据进行两位舍余
            $principal = $deal_model->floorfix($principal);
            $prepay_money = $deal_model->floorfix($prepay_money);
            $prepay_interest = $deal_model->floorfix($prepay_interest);
            //$prepay_compensation = $deal_model->floorfix($prepay_money - $prepay_interest - $principal);
            // 保存提前还款回款计划
            $this->savePrepayDealLoanRepay($deal, $prepay, $deal_load, $principal, $prepay_interest, $prepay_compensation);

            // 处理超出充值
            DealLoanRepayModel::instance()->repairMoneyOnrepay($deal_load['id'], $principal, $deal_load['user_id']);

            // 投资用户回款
            $user = UserModel::instance()->find($deal_load['user_id']);
            $user->changeMoneyDealType = $dealService->getDealType($deal);


            // 资管转账
            $syncRemoteData = array();
            $deal_service = new DealService();
            $isDT = $deal_service->isDealDT($prepay->deal_id);
            $isDealXH = $deal_service->isDealYtsh($prepay->deal_id);
            $bizToken = [
                'dealId' => $deal['id'],
            ];
            if ($isDT === true) {
                // 如果标的属于智多星，只更改本金还款状态，不操作账户变更，不变更还款日历，不变更资产总额；利息会款到利息账户
                //智多鑫还款依赖智多鑫计算，此处不处理
//                $user_id_interest = app_conf('AGENCY_ID_DT_INTEREST');
//                $user_interest = UserModel::instance()->find($user_id_interest);
//                $user_interest->changeMoneyDealType = $dealService->getDealType($deal);
//                $user_interest->changeMoney($prepay_interest, '提前还款利息', '编号' . $deal['id'] . ' ' . $deal['name']);
//                $user_interest->changeMoney($prepay_compensation, '提前还款补偿金', '编号' . $deal['id'] . ' ' . $deal['name']);
            } else {
                $user_id_interest = $deal_load['user_id'];
                // 投资用户回款
                $user = UserModel::instance()->find($deal_load['user_id']);
                $user->changeMoneyDealType = $dealType;
                $user->changeMoney($principal, '提前还款本金', '编号' . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);


                //哈哈农庄化肥标逻辑（将投资用户本金转入云图控股用户账户)
                $isHF = $deal_service->isDealHF($deal['id']);
                if($isHF){
                    $userModel = new UserModel();
                    $userHF = $userModel->find(app_conf('CLOUD_PIC_USER_ID'));
                    $user->changeMoney(-$principal, "授权转出", "编号{$deal['id']} 标的名称{$deal['name']},本金授权转出", 0, 0, 0, 0, $bizToken);
                    $userHF->changeMoney($principal, "授权转入", "编号:{$deal['id']},标的名称:{$deal['name']},{$user->real_name}(".moblieFormat($user->mobile).")授权转入", 0, 0, 0, 0, $bizToken);
                }elseif($credit_loan_service->isCreditingUser($deal_load['user_id'],$deal['id'])){
                    $user->changeMoney($principal, '贷款冻结', '冻结 "' . $deal['name'] .'" 投资本金',0,0,UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
                }elseif($isNeedFreeze === true){
                    /** 如果用户发生过借款 冻结用户本金  $credit_loan_service */
                    $user->changeMoney($principal, '网信速贷还款冻结', '冻结 "' . $deal['name'] .'" 投资本金',0,0,UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
                    $credit_loan_service->freezeNotifyCreditloan($deal_load['user_id'],$deal['id'],$prepay_id,3);
                }

                if($isDealExchange) {//大金所收集
                    $money_info[UserLoanRepayStatisticsService::JS_NOREPAY_PRINCIPAL] = -$principal;
                    $money_info[UserLoanRepayStatisticsService::JS_NOREPAY_EARNINGS] = -$money_cancel;
                    $money_info[UserLoanRepayStatisticsService::JS_TOTAL_EARNINGS] = $prepay_interest;
                }
                if($isDealCG) {// 存管网贷
                    $money_info[UserLoanRepayStatisticsService::CG_NOREPAY_PRINCIPAL] = -$principal;
                    $money_info[UserLoanRepayStatisticsService::CG_NOREPAY_EARNINGS] = -$money_cancel;
                    $money_info[UserLoanRepayStatisticsService::CG_TOTAL_EARNINGS] = $prepay_interest;
                }

                // 处理回款日历
                $calInfo[DealLoanRepayCalendarService::PREPAY_PRINCIPAL] = $principal;
                $calInfo[DealLoanRepayCalendarService::PREPAY_INTEREST] = $prepay_interest;

                if (bccomp($prepay_compensation, '0.00', 2) > 0) {
                    $money_info[UserLoanRepayStatisticsService::LOAD_TQ_IMPOSE] = $prepay_compensation;
                    $calInfo[DealLoanRepayCalendarService::PREPAY_INTEREST] +=$prepay_compensation;
                }

                $user->changeMoney($prepay_interest, '提前还款利息', '编号' . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);
                $user->changeMoney($prepay_compensation, '提前还款补偿金', '编号' . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);

                // 处理资产总额
                //$money_info[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] = $principal;
                $money_info[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL] = -$principal;
                $money_info[UserLoanRepayStatisticsService::LOAD_EARNINGS] = $prepay_interest;

                $money_info[UserLoanRepayStatisticsService::NOREPAY_INTEREST] = -$money_cancel;

                if (UserLoanRepayStatisticsService::updateUserAssets($deal_load['user_id'], $money_info) === false) {
                    throw new \Exception("user loan repay statistics error");
                }

                if (DealLoanRepayCalendarService::collect($deal_load['user_id'], time(), $calInfo) === false) {
                    throw new \Exception("save calendar error");
                }

                $repayMoney= bcadd(bcadd($principal, $prepay_interest, 2), $prepay_compensation,2);
                if(!JfTransferService::instance()->repayTransferToJf($user,$deal['id'],$deal_loan_id,$repayMoney)) {
                    throw new \Exception("JfTransferService error");
                }

                if (bccomp($principal, '0.00', 2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => 'PREPAYDEAL|' . $deal_load['id'],
                        'payerId' => $prepay_user_id,
                        'receiverId' => $deal_load['user_id'],
                        'repaymentAmount' => bcmul($principal, 100),
                        'curType' => 'CNY',
                        'bizType' => 1,
                        'batchId' => $deal['id'],
                    );
                }

                if($isHF){
                    if (bccomp($principal, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'PREPAYDEALHF|' . $deal_load['id'],
                            'payerId' => $deal_load['user_id'],
                            'receiverId' => app_conf('CLOUD_PIC_USER_ID'),
                            'repaymentAmount' => bcmul($principal, 100),
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }
            }

            if (bccomp(bcadd($prepay_compensation, $prepay_interest, 2) ,'0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'PREPAYINTEREST|' . $deal_load['id'],
                    'payerId' => $prepay_user_id,
                    'receiverId' => $user_id_interest,
                    'repaymentAmount' => bcmul(bcadd($prepay_compensation, $prepay_interest, 4), 100),
                    'curType' => 'CNY',
                    'bizType' => 1,
                    'batchId' => $deal['id'],
                );
            }

            if(!$dealService->isP2pPath($deal)){
                if (!FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH)) {
                    throw new \Exception("FinanceQueueModel push error");
                }
            }

            if($isDealXH){
                $xhMoney = bcadd($principal,$prepay_interest,2);
                $user->changeMoney($xhMoney, '享花还款冻结', '冻结 "' . $deal['name'] .'" 投资本息',0,0,UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            }

            //判断是否使用了加息券
            $o2oDiscountRateService = new O2ODiscountRateService();
            if($o2oDiscountRateService->isNeedDiscountRate($deal_loan_id)) {
                $jobs_model = new JobsModel();
                $function = '\core\service\oto\O2ODiscountRateService::useDiscountRate';
                $token = \libs\utils\Token::genToken();
                $param = array(
                    'token' => $token,
                    'dealLoanId' => $deal_loan_id,
                    'prepayInfo' => array(
                        'principal' => $principal,
                        'remain_days' => $prepay->remain_days,
                    ),
                );
                $jobs_model->priority = 85;
                $r = $jobs_model->addJob($function, $param, false, 90);
                if ($r === false) {
                    throw new \Exception("add O2ODiscountRateService useDiscountRate jobs error");
                }
            }

            $this->db->commit();

            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, $deal_loan_id, $deal_load['user_id'], "succ")));
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $prepay->deal_id, $prepay_id, $deal_loan_id, $deal_load['user_id'], "fail", $e->getMessage(), $e->getLine())));
            throw $e;
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
        $dlr_model->status = DealLoanRepayModel::STATUS_ISPAYED;
        $dlr_model->time = to_timespan(to_date(get_gmtime(),'Y-m-d'));
        $dlr_model->real_time = to_timespan(to_date(get_gmtime(),'Y-m-d'));
        $dlr_model->create_time = get_gmtime();
        $dlr_model->update_time = get_gmtime();
        $dlr_model->deal_type = $deal_repay['deal_type'];

        // 本金
        $dlr_model->type = DealLoanRepayModel::MONEY_PREPAY;
        $dlr_model->money = $prepay_principal;
        if ($dlr_model->insert() === false) {
            throw new \Exception("deal_loan_repay insert fail");
        }

        // 利息
        $dlr_model->type = DealLoanRepayModel::MONEY_PREPAY_INTREST;
        $dlr_model->money = $prepay_interest;
        if ($dlr_model->insert() === false) {
            throw new \Exception("deal_loan_repay insert fail");
        }

        // 补偿金
        $dlr_model->type = DealLoanRepayModel::MONEY_COMPENSATION;
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
}
