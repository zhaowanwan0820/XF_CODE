<?php
namespace core\service\repay;

use core\dao\deal\DealExtModel;
use core\dao\repay\DealLoanRepayModel;
use core\enum\DealLoanRepayEnum;
use core\enum\MsgBoxEnum;
use core\enum\DealExtEnum;
use core\service\msgbox\MsgboxService;
use libs\utils\Finance;
use libs\utils\Logger;
use core\enum\DealEnum;
use core\enum\JobsEnum;
use core\enum\DealRepayEnum;
use core\service\repay\RepayBaseService;
use core\dao\deal\DealModel;
use core\dao\jobs\JobsModel;
use core\dao\deal\DealLoadModel;
use core\enum\DealRepayOpLogEnum;
use core\service\deal\DealService;
use core\dao\repay\DealPrepayModel;
use NCFGroup\Common\Library\Idworker;
use core\dao\repay\DealRepayOplogModel;
use core\service\user\UserCarryService;
use core\service\repay\DealPrepayMoneyLog;
use core\dao\supervision\SupervisionWithdrawAuditModel;


class DealPrepayService extends RepayBaseService {

    protected $deal;

    protected $dealExt;

    public function setDeal($deal){
        $this->deal = $deal;

        $this->dealExt = DealExtModel::instance()->findBy('deal_id='.$deal->id);
    }

    public function checkCanPrepay() {
        if(!$this->deal){
            throw new \Exception('deal is null');
        }
        if($this->deal->deal_status != DealEnum::DEAL_STATUS_REPAY){
            throw new \Exception("当前状态不允许提前还款");
        }

        if($this->deal->is_has_loans != DealEnum::DEAL_IS_HAS_LOANS_YES) {
            throw new \Exception("当前状态不允许提前还款");
        }
        if($this->deal->is_during_repay == DealEnum::DEAL_DURING_REPAY){
            throw new \Exception("操作失败， 标的正在还款中");
        }
        return true;
    }

    public function prepay($param){
        $id = isset($param['deal_repay_id']) ? intval($param['deal_repay_id']) : intval($param['id']);
        if (empty($id)) {
            throw new \Exception('id is null');
        }
        $adminInfo = $param["admInfo"];
        $orderId = $param['orderId'];
        $prepay = DealPrepayModel::instance()->find($id);
        if(!$prepay || $prepay->status != DealRepayEnum::PREPAY_AUDIT_STATUS_PASSED){
            throw new \Exception('提前还款信息不存在');
        }
        if($prepay->status == DealRepayEnum::PREPAY_STATUS_REPAYED){
            throw new \Exception('已还款，请勿重复操作');
        }

        $deal = DealModel::instance()->find($prepay->deal_id);
        $this->setDeal($deal);
        if($this->deal->is_during_repay != DealEnum::DEAL_DURING_REPAY){
            throw new \Exception('当前状态不允许提前还款');
        }

        $repayType = $prepay->repay_type; // 还款账户类型


        $dealService = new DealService();
        $prepayUserId  = $dealService->getRepayUserAccount($deal->id,$repayType);

        try{
            $GLOBALS['db']->startTrans();

            if ($repayType == DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH) {
                RepayMoneyLogRoute::handleMoneyLog($this->deal,$prepay,DealRepayEnum::DEAL_REPAY_PREYAY_DZH,$repayType);
            } else {
                RepayMoneyLogRoute::handleMoneyLog($this->deal,$prepay,DealRepayEnum::DEAL_REPAY_PREPAY,$repayType);
            }

            //还款时候触更新改投资体现限制
            $userCarryService = new UserCarryService();
            $rs = $userCarryService->updateWithdrawLimitAfterRepalyMoney($prepayUserId,$prepay->prepay_money);
            if($rs === false){
                throw new \Exception("更新投资体现限制失败");
            }

            if ($dealService->isDealDTV3($prepay->deal_id) === true) {
                $jobs_model = new JobsModel();
                $function = '\core\dao\repay\DealPrepayModel::prepayDtV3';
                $param = array(
                    'prepay_id' => $prepay->id,
                    'prepay_user_id' => $prepayUserId,
                );
                $jobs_model->priority = JobsEnum::PRIORITY_REPAY_DEAL_LOAN;
                $r = $jobs_model->addJob($function, array('param' => $param));
                if ($r === false) {
                    throw new \Exception("add prepay by loan id jobs error");
                }
            } else {
                $arr_deal_load = DealLoadModel::instance()->getDealLoanList($prepay->deal_id);
                foreach ($arr_deal_load as $k => $deal_load) {
                    $jobs_model = new JobsModel();
                    $function = '\core\dao\repay\DealPrepayModel::prepayByLoanId';
                    $param = array(
                        'deal_loan_id' => $deal_load['id'],
                        'prepay_id' => $prepay->id,
                        'prepay_user_id' => $prepayUserId,
                        'repay_type' => $repayType,
                    );
                    $jobs_model->priority = JobsEnum::PRIORITY_REPAY_DEAL_LOAN;
                    $r = $jobs_model->addJob($function, array('param' => $param));
                    if ($r === false) {
                        throw new \Exception("add prepay by loan id jobs error");
                    }
                }
            }

            $jobs_model = new JobsModel();
            $function = '\core\dao\repay\DealPrepayModel::finishPrepay';
            $param = array('prepay_id' => $prepay->id,'user_id' => $prepay->user_id ,'prepayUserId'=>$prepayUserId,'orderId' => $orderId);
            $jobs_model->priority = JobsEnum::PRIORITY_REPAY_DEAL_LOAN;
            $r = $jobs_model->addJob($function, array('param' => $param), false, 90);
            if ($r === false) {
                throw new \Exception("add finish prepay jobs error");
            }

            if ($repayType == DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH) {
                $save_res = $this->addDZHDealPrepayOplog($prepay, $deal, $adminInfo, $param['submitUid']);
            } else {
                //增加提前还款的操作记录
                $repayOpLog = new DealRepayOplogModel();
                $repayOpLog->operation_type = DealRepayOpLogEnum::REPAY_TYPE_PRE;//提前还款
                $repayOpLog->operation_time = get_gmtime();
                $repayOpLog->operation_status = 1;
                $repayOpLog->operator = $adminInfo['adm_name'];
                $repayOpLog->operator_id = $adminInfo['adm_id'];

                $repayOpLog->deal_id = $this->deal['id'];
                $repayOpLog->deal_name = $this->deal['name'];
                $repayOpLog->borrow_amount = $this->deal['borrow_amount'];
                $repayOpLog->rate = $this->deal['rate'];
                $repayOpLog->loantype = $this->deal['loantype'];
                $repayOpLog->repay_period = $this->deal['repay_time'];
                $repayOpLog->user_id = $this->deal['user_id'];
                $repayOpLog->submit_uid = intval($param['submitUid']);

                $repayOpLog->deal_repay_id = $prepay->id;
                $repayOpLog->repay_money = $prepay->prepay_money;
                $repayOpLog->real_repay_time = get_gmtime();

                //存管&&还款方式
                $repayOpLog->repay_type = $prepay->repay_type;

                $save_res = $repayOpLog->save();
            }
            if(!$save_res) {
                throw new \Exception("插入还款操作记录失败");
            }
            $GLOBALS['db']->commit();
            Logger::info("dealAction->doPrepare(pass deal_id['" . $prepay->deal_id . "'], repay_id['" . $id . "]')");
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error("dealAction->doPrepare(pass deal_id['" . $prepay->deal_id . "'], repay_id['" . $id . "]'), msg[" . $ex->getMessage() . "], line:[" . $ex->getLine() . "]");
            throw $ex;
        }
        return true;
    }

    public function addDZHDealPrepayOplog($prepay, $deal, $adminInfo, $submitUid) {
        $partialRepayModel = new \core\dao\repay\PartialRepayModel();
        $borrowerPrepayMoney = $partialRepayModel->getPrepayMoney($prepay->id, \core\enum\PartialRepayEnum::REPAY_TYPE_BORROWER);
        $rechargePrepayMoney = $partialRepayModel->getPrepayMoney($prepay->id, \core\enum\PartialRepayEnum::REPAY_TYPE_COMPENSATORY);
        $logArray=array();
        if((bccomp($borrowerPrepayMoney, '0.00', 2) == 1) && (bccomp($rechargePrepayMoney, '0.00', 2) == 1)) {
            $logArray[] = array('repay_money' => $borrowerPrepayMoney, 'repay_type' => DealRepayEnum::DEAL_REPAY_TYPE_PART_SELF);
            $logArray[] = array('repay_money' => $rechargePrepayMoney, 'repay_type' => DealRepayEnum::DEAL_REPAY_TYPE_PART_DAICHANG);
        } else {
            if(bccomp($borrowerPrepayMoney, '0.00', 2) == 1) {
                $logArray[] = array('repay_money' => $borrowerPrepayMoney,'repay_type'=>DealRepayEnum::DEAL_REPAY_TYPE_SELF);
            } else {
                $logArray[] = array('repay_money' => $rechargePrepayMoney,'repay_type'=>DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI);
            }
        }

        $return = true;
        foreach ($logArray as $logInfo) {
            //增加提前还款的操作记录
            $repayOpLog = new DealRepayOplogModel();
            $repayOpLog->operation_type = DealRepayOpLogEnum::REPAY_TYPE_PRE;//提前还款
            $repayOpLog->operation_time = get_gmtime();
            $repayOpLog->operation_status = 1;
            $repayOpLog->operator = $adminInfo['adm_name'];
            $repayOpLog->operator_id = $adminInfo['adm_id'];

            $repayOpLog->deal_id = $deal['id'];
            $repayOpLog->deal_name = $deal['name'];
            $repayOpLog->borrow_amount = $deal['borrow_amount'];
            $repayOpLog->rate = $deal['rate'];
            $repayOpLog->loantype = $deal['loantype'];
            $repayOpLog->repay_period = $deal['repay_time'];
            $repayOpLog->user_id = $deal['user_id'];
            $repayOpLog->submit_uid = intval($submitUid);

            $repayOpLog->deal_repay_id = $prepay->id;
            $repayOpLog->repay_money = $logInfo['repay_money'];
            $repayOpLog->real_repay_time = get_gmtime();

            //存管&&还款方式
            $repayOpLog->repay_type = $logInfo['repay_type'];

            $result = $repayOpLog->save();
            if (!$result) {
                $return  = false;
                break;
            }
        }

        return $return;
    }


    /**
     * @param $end_day 结束日期：2016-02-12
     */
    public function prepayCalc($end_day, $is_check_prepay_days_limit = false) {
        if(!$this->deal){
            throw new \Exception('标的未初始化');
        }

        if(!preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $end_day)) {
            throw new \Exception("结束日期{$end_day}格式不正确");
        }

        // 计算计息日期
        $dps = new DealRepayService();
        $interest_time =  $dps->getMaxRepayTimeByDeal($this->deal);

        // 因为$interest_time 有可能不是从零点开始记录的，所以计算天数会有误差
        $interest_time = to_timespan(to_date($interest_time,'Y-m-d')); // 转换为零点开始
        $end_interest_time = to_timespan($end_day); // 计息结束日期

        $calc_interest_time = $end_interest_time; // 实际计算的计息结束日期

        $remain_days = ceil(($calc_interest_time - $interest_time)/86400); // 利息天数

        $deal_pass_days = ceil(($calc_interest_time - $this->deal['repay_start_time'])/86400); // 标的放款后已经过去的天数

        if ($is_check_prepay_days_limit && $this->deal['prepay_days_limit'] > $deal_pass_days) {
            $end_day_timestamp = strtotime($end_day) + ($this->deal['prepay_days_limit'] - $remain_days) * 86400;
            $end_day = date('Y-m-d', $end_day_timestamp);
            $remain_days = $this->deal['prepay_days_limit'];
        }

        if($end_interest_time <= $interest_time) {
            throw new \Exception("计息结束日期必须大于计息日期");
        }

        $deal_loan_repay_model = new DealPrepayModel();

        $remain_principal = $this->deal->getRemainPrincipal();

        $prepay_result = $deal_loan_repay_model->getPrepayMoney($this->deal->id,$remain_principal, $remain_days);

        $prepay_money = $prepay_result['prepay_money']; // 还款总额
        $remain_principal = $prepay_result['principal']; // 应还本金
        $prepay_interest = $prepay_result['prepay_interest']; // 应还利息


        $remain_principal = floorfix($remain_principal);
        $prepay_interest = floorfix($prepay_interest);
        $prepay_compensation = floorfix($prepay_money - $prepay_interest - $remain_principal);
        //$prepay_compensation = $deal_dao->floorfix($deal['borrow_amount'] * $deal['prepay_rate'] / 100); // 借款金额x提前还款违约金系数

        // 各项未收费用
        $deal_repay_model = new \core\dao\repay\DealRepayModel();
        $fees = $deal_repay_model->getNoPayFees($this->deal,$this->dealExt,$end_day);

        $prepay_money = floorfix($prepay_money + $fees['loan_fee'] + $fees['consult_fee'] + $fees['guarantee_fee'] + $fees['pay_fee'] + $fees['canal_fee']);
        //$prepay_money = bcsub($prepay_money,$prepay_compensation,2);


        if ($this->dealExt['pay_fee_rate_type'] == 1) {
            $fee_days = ceil(($calc_interest_time - $this->deal['repay_start_time'])/86400); // 费用天数
            $pay_fee_rate = Finance::convertToPeriodRate($this->deal['loantype'], $this->deal['pay_fee_rate'], $this->deal['repay_time'], false);
            $pay_fee = floorfix($this->deal['borrow_amount'] * $pay_fee_rate / 100.0);

            $pay_fee_rate_real = Finance::convertToPeriodRate($this->deal['loantype'], $this->deal['pay_fee_rate'], $fee_days, false);
            $pay_fee_real = floorfix($this->deal['borrow_amount'] * $pay_fee_rate_real / 100.0);
            $pay_fee_remain = bcsub($pay_fee, $pay_fee_real, 2);
        }

        $data = array(
            'deal_id'             => $this->deal['id'],
            'user_id'             => $this->deal['user_id'],
            'interest_time'       => $interest_time, // 计息日期
            'prepay_time'         => $end_interest_time, // 提前还款日期
            'remain_days'         => $remain_days, // 利息天数
            'prepay_money'        => $prepay_money,
            'remain_principal'    => $remain_principal,
            'prepay_interest'     => $prepay_interest,
            'prepay_compensation' => $prepay_compensation,
            'loan_fee'            => $fees['loan_fee'],
            'consult_fee'         => $fees['consult_fee'],
            'guarantee_fee'       => $fees['guarantee_fee'],
            'management_fee'      => 0,// 暂时不考虑智多鑫管理费
            'pay_fee'             => $fees['pay_fee'],
            'canal_fee'           => $fees['canal_fee'],
            'pay_fee_remain'      => !empty($pay_fee_remain) ? $pay_fee_remain : 0,
            'deal_type'           => $this->deal['deal_type'],
        );
        return $data;
    }


    public function prepaySave($data) {

        $sql = "select * from ".DB_PREFIX."deal_prepay where deal_id= ".$this->deal->id." and status =0";
        $res = $GLOBALS['db']->getRow($sql);

        if($res) {
            $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"UPDATE","id=".$res['id']);
            if ($res == false) {
                throw new \Exception("insert deal_prepay error deal_id:".$this->deal->id);
            }
        }else{
            $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"INSERT");
            if ($res == false) {
                throw new \Exception("update deal_prepay error deal_id:".$this->deal->id);
            }
        }
        return true;
    }


    /**
     * 提前还款一条龙服务
     * @param $dealId 提前还款的标的ID
     * @param $endDate 提前还款 计息结束日期
     * @param $repayType 还款账户类型
     * @param $adminInfo 提前还款操作日志信息
     * @param $isBorrowerSelf 是否客户自助提前还款
     */
    public function prepayPipeline($dealId,$endDate,$repayType,$adminInfo=array(),$orderId = '') {
        Logger::info(implode(',',array(__CLASS__,__FUNCTION__,"prepay begin"," deal_id:{$dealId},endDate:{$endDate},repayAccountType:{$repayType},orderId:{$orderId}")));
        $adminInfo['adm_id'] = !isset($adminInfo['adm_id']) ? '0' : $adminInfo['adm_id'];
        $adminInfo['adm_name'] = !isset($adminInfo['adm_name']) ? 'system' : $adminInfo['adm_name'];

        $startTrans = false;
        try{
            $deal = DealModel::instance()->find($dealId);
            if(!$deal){
                throw new \Exception('标的信息不存在');
            }
            $ds = new DealService();
            $repayUserId = $ds->getRepayUserAccount($dealId,$repayType);
            if(!$repayUserId) {
                throw new \Exception("获取还款用户ID失败deal_id:{$dealId}");
            }

            $this->setDeal($deal);
            $endInterestDay = $endDate;// 计息结束日期
            $retry = 0; // 无重试保证，不能重试

            $this->checkCanPrepay();
            $GLOBALS['db']->startTrans();
            $startTrans = true;

            // 提前还款各项金额计算
            $calcInfo = $this->prepayCalc($endInterestDay, ($repayType == DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH));
            $calcInfo['status'] = 1; // 自动审核通过
            $calcInfo['repay_type'] = $repayType; // 借款人

            $this->prepaySave($calcInfo); // 保存提前还款信息

            $prepay = new \core\dao\repay\DealPrepayModel();
            $prepay = $prepay->findBy("deal_id=".$dealId);

            // 将标的置为还款中
            $res = $this->deal->changeDuringRepay($dealId);
            if (!$res) {
                throw new \Exception("chage repay status error");
            }

            // 还款总额 = 应还本金+应还利息+手续费+咨询费+担保费+支付服务费。
            $prepay_money = $prepay['prepay_money'];

            $ds = new DealService();
            $param = array('id' => $prepay->id, 'admInfo' => $adminInfo);
            if ($orderId == '') {
                $orderId = Idworker::instance()->getId();
            }
            $function = '\core\service\repay\P2pDealRepayService::dealPrepayRequest';
            $param = array('orderId'=>$orderId,'prepayId'=>$prepay->id,'params'=>$param);

            // 启动jobs进行还款操作
            $job_model = new JobsModel();
            $job_model->priority = JobsEnum::PRIORITY_DEAL_PREPAY;
            $job_model->addJob($function, array('param' => $param), false, $retry);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            if ($startTrans == true) {
                $GLOBALS['db']->rollback();
            }
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,$ex->getMessage())));
            throw $ex;
        }
        try {
            // 先计息后放款的,更新网贷账户放款提现审核状态 更新失败不影响还款
            $dealExt = DealExtModel::instance()->getDealExtByDealId($dealId);
            if ($dealExt['loan_type'] == DealExtEnum::LOAN_TYPE_LATER_LOAN) {
                $logInfo = __CLASS__ . ' ' . __FUNCTION__ . ' '.$deal['user_id'] . ' ' . $dealId.' ';
                $supervisionWithdrawAuditModel = new SupervisionWithdrawAuditModel();
                $ret = $supervisionWithdrawAuditModel->repayWithdrawAudit($deal['user_id'], $dealId);
                if ($ret['respCode'] != 0) {
                    Logger::error( $logInfo.$ret['respMsg']);
                }
                Logger::info($logInfo);
            }
        }catch (\Exception $e) {
            Logger::error($logInfo . $e->getMessage());
        }
        $params = array('prepayId'=>$prepay->id ,'dealId' =>$dealId, 'endDate' => $endDate, 'repayType' => $repayType, 'adminInfo' => $adminInfo);
        Logger::info(implode(',',array('提前还jobs加入成功',__CLASS__,__FUNCTION__,"prepay success params:".json_encode($params))));
        return true;
    }

    /**
     * 以合并多笔投资的方式发送提前回款站内信
     * @param DealPrepayModel $prepay
     * @param DealModel $deal
     * @param int $loan_user_id
     * @return
     */
    public function sendDealPrepayMessage($prepay, $deal, $loan_user_id)
    {
        $deal_service = new DealService();
        if (true ===  $deal_service->isDealDT($deal->id)) {
            return;
        }
        if (true ===  $deal_service->isDealDTV3($deal->id)) {
            return;
        }

        // 发消息开始
        $principal = $deal->floorfix(DealLoanRepayModel::instance()->getTotalMoneyOfUserByDealId($prepay->deal_id, $loan_user_id, DealLoanRepayEnum::MONEY_PREPAY, DealLoanRepayEnum::STATUS_ISPAYED, true));
        $prepay_interest = $deal->floorfix(DealLoanRepayModel::instance()->getTotalMoneyOfUserByDealId($prepay->deal_id, $loan_user_id, DealLoanRepayEnum::MONEY_PREPAY_INTREST, DealLoanRepayEnum::STATUS_ISPAYED, true));
        $prepay_compensation = $deal->floorfix(DealLoanRepayModel::instance()->getTotalMoneyOfUserByDealId($prepay->deal_id, $loan_user_id, DealLoanRepayEnum::MONEY_COMPENSATION, DealLoanRepayEnum::STATUS_ISPAYED, true));
        $prepay_money = $principal + $prepay_interest + $prepay_compensation;

        $prepay_money_format = format_price($prepay_money);
        $principal_format = format_price($principal);
        $prepay_interest_format = format_price($prepay_interest);
        $prepay_compensation_format = format_price($prepay_compensation);

        $content = sprintf('您投资的“%s”发生提前还款，总额%s，其中提前还款本金%s，提前还款利息%s，提前还款补偿金%s。本次投资已回款完毕。', $deal->name, $prepay_money_format, $principal_format, $prepay_interest_format, $prepay_compensation_format);


        $load_counts = DealLoadModel::instance()->getDealLoadCountsByUserId($deal->id, $loan_user_id, true);
        $structured_content = array(
            'money' => sprintf('+%s', number_format($prepay_money, 2)),
            'repay_periods' => '已完成', // 期数
            'main_content' => rtrim(sprintf("%s%s%s%s",
                sprintf("项目：%s（%s笔）\n", $deal['name'], $load_counts),
                empty($principal) ? '' : sprintf("本金：%s\n", $principal_format),
                empty($prepay_interest) ? '' : sprintf("收益：%s\n", $prepay_interest_format),
                empty($prepay_compensation) ? '' : sprintf("提前还款补偿金：%s\n", $prepay_compensation_format)
            )),
            'is_last' => 1,
            'prepay_tips' => '提前回款',
            'turn_type' => MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST, // app 跳转类型标识
        );

        $msgbox = new MsgboxService();
        $msgbox->create($loan_user_id, 9, '回款', $content, $structured_content);
    }
}