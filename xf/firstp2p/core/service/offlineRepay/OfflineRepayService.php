<?php

namespace core\service\offlineRepay;

use app\models\dao\DealLoanRepay;
use core\dao\DealLoadModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\DealRepayOplogModel;
use core\dao\JobsModel;
use core\dao\UserModel;
use core\service\DealLoanRepayCalendarService;
use core\service\DealService;
use core\service\UserLoanRepayStatisticsService;
use libs\utils\Logger;

class OfflineRepayService {

    const MONEY_PRINCIPAL = 1; // 本金
    const MONEY_INTREST = 2; // 利息
    const MONEY_PREPAY = 3; // 提前还款
    const MONEY_COMPENSATION = 4; // 提前还款补偿金
    const MONEY_IMPOSE = 5; // 逾期罚息
    const MONEY_MANAGE = 6; // 管理费
    const MONEY_PREPAY_INTREST = 7; // 提前还款利息
    const MONEY_COMPOUND_PRINCIPAL = 8; // 利滚利赎回本金
    const MONEY_COMPOUND_INTEREST = 9; // 利滚利赎回利息

    // 判断如果当期是未还状态修改为已还同时判断是否需要改为已结清
    public function preRepay($dealId,$repayId,$authKey,$audit){
        $deal = DealModel::instance()->find($dealId);
        $dealRepayModel = DealRepayModel::instance()->find($repayId);
        $next_repay = $dealRepayModel->getNextRepay();

        $time = get_gmtime();
        if($deal->is_during_repay ==1){
            throw new \Exception("标的正在还款中");
        }
        $changeRes = $deal->changeRepayStatus(1);
        if(!$changeRes){
            throw new \Exception('标的状态修改失败');
        }

        if($dealRepayModel->status >0){
            return true;
        }

//        if(to_date($dealRepayModel->repay_time, "Y-m-d") >= to_date($time, "Y-m-d")){
//            $dealRepayModel->status = 1; //准时
//        }else{
//            $dealRepayModel->status = 2; //逾期
//        }
        $dealRepayModel->status = 100;
        $dealRepayModel->true_repay_time = $time;
        $dealRepayModel->update_time = $time;
        $res = $dealRepayModel->save();
        if(!$res){
            throw new \Exception("还款计划状态修改失败");
        }

        $deal->repay_money = bcadd($deal->repay_money,$dealRepayModel->repay_money,2);
        $deal->last_repay_time = $time;
        $deal->update_time = $time;

        if (!$deal->save()) {
            throw new \Exception('订单还款额修改失败！');
        }

        //添加还款操作记录
        $admin = \es_session::get(md5($authKey));

        $repayOpLog = new DealRepayOplogModel();
        $repayOpLog->operation_type = DealRepayOplogModel::REPAY_TYPE_NORMAL;//正常还款
        $repayOpLog->operation_time = get_gmtime();
        $repayOpLog->operation_status = 1;
        $repayOpLog->operator = $admin['adm_name'];
        $repayOpLog->operator_id = $admin['adm_id'];
        //标的信息
        $repayOpLog->deal_id = $deal['id'];
        $repayOpLog->deal_name = $deal['name'];
        $repayOpLog->borrow_amount = $deal['borrow_amount'];
        $repayOpLog->rate = $deal['rate'];
        $repayOpLog->loantype = $deal['loantype'];
        $repayOpLog->repay_period = $deal['repay_time'];
        $repayOpLog->user_id = $deal['user_id'];

        //存管&&还款方式
        $repayOpLog->repay_type = 100;
        $repayOpLog->report_status = $deal['report_status'];

        //还款的信息
        $repayOpLog->deal_repay_id = $repayId;
        $repayOpLog->repay_money = $dealRepayModel->repay_money;
        $repayOpLog->real_repay_time = get_gmtime();
        $repayOpLog->submit_uid = intval($audit['submit_uid']);
        $repayOpLog->audit_type= 0;
        if (!$repayOpLog->save()) {
            throw new \Exception('还款操作日志记录失败！');
        }
        return true;
    }

    public function repay($repayId,$loanIds=[],$authKey,$audit){
        if(empty($loanIds)){
            throw new \Exception('投资ID不能为空');
        }
        $time = get_gmtime();
        $dealRepay = DealRepayModel::instance()->find($repayId);

        $dealLoan = new DealLoadModel();
        $dealLoanList = $dealLoan->getDealLoanList($dealRepay->deal_id);
        if(empty($dealLoanList)){
            throw new \Exception('投资记录为空');
        }


        try{
            $GLOBALS['db']->startTrans();
            $this->preRepay($dealRepay->deal_id,$repayId,$authKey,$audit);

            foreach ($dealLoanList as $deal_loan) {
                if(!in_array($deal_loan->id,$loanIds)){
                    continue;
                }
                //插入队列执行
                $jobs_model = new JobsModel();
                $function = '\core\service\offlineRepay\OfflineRepayService::offlineRepayDealLoanOne';
                $param = array(
                    'deal_repay_id' => $repayId,
                    'deal_loan_money' => $deal_loan->money,
                    'deal_loan_id' => $deal_loan->id,
                    'deal_loan_user_id' => $deal_loan->user_id,
                    'ignore_impose_money' => false,
                    'next_repay_id' => false,
                );
                $jobs_model->priority = 85;
                $r = $jobs_model->addJob($function, array('param' => $param));
                if ($r === false) {
                    throw new \Exception("add offlineRepay by loan id jobs error");
                }
            }



            // 加入还款结束检查
            $jobs_model = new JobsModel();
            $function = '\core\service\offlineRepay\OfflineRepayService::finishRepay';
            $param = array(
                'deal_id' => $dealRepay->deal_id,
                'deal_repay_id' => $repayId,
            );
            $jobs_model->priority = 85;
            $r = $jobs_model->addJob($function, array('param' => $param), false, 90);
            if ($r === false) {
                throw new \Exception('add \core\service\offlineRepay\OfflineRepayService::finishRepay error');
            }
            $rs = $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            throw new \Exception($ex->getMessage());
        }
        if ($rs === false) {
            throw new \Exception("事务提交失败");
        }
        return true;
    }

    public function finishRepay($param){
        $deal = DealModel::instance()->find($param['deal_id']);

        //检查这次还款的数量如果还有，那就等着
        $count = DealLoanRepayModel::instance()->getRepayCountByDealRepayId($param['deal_repay_id']);

        
        //if($count>0){
            //throw new \Exception(JobsModel::ERRORMSG_NEEDDELAY, JobsModel::ERRORCODE_NEEDDELAY);
        //}

        //修改标状态
        $save_res = $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
        if(!$save_res){
            throw new \Exception('修改标的还款状态失败！');
        }

        // 检查标的是否能置为已还清状态
        $count = DealLoanRepayModel::instance()->getRepayCountByDeal($param['deal_id']);
        if($count == 0){
            $deal->repayCompleted();
        }

        \libs\utils\Monitor::add('DEAL_REPAY');
        return true;
    }

    public function offlineRepayDealLoanOne($param)
    {
        $deal_repay_id = $param['deal_repay_id'];
        $deal_loan_money = $param['deal_loan_money'];
        $deal_loan_id = $param['deal_loan_id'];
        $deal_loan_user_id = $param['deal_loan_user_id'];

        $deal_service = new DealService();
        $moneyInfo = array();
        $calInfo = array();

        $realTime = to_timespan(to_date(get_gmtime(),'Y-m-d'));// 提前还款的真实时间是 今日零点

        $deal_repay_model = new DealRepayModel();
        $deal_repay = $deal_repay_model->find($deal_repay_id);
        $deal_id = intval($deal_repay->deal_id);
        $deal_repay_id = intval($deal_repay->id);
        $deal = DealModel::instance()->find($deal_id);


        $condition = "`deal_repay_id`= '%d' AND `deal_loan_id` = '%d' AND `loan_user_id`= '%d' AND status = 0";
        $condition = sprintf($condition, ($deal_repay_id), ($deal_loan_id), ($deal_loan_user_id));
        //根据还款记录ID，投标记录ID，投资人ID
        $loan_repay_list = DealLoanRepay::instance()->findAll($condition);

        if(empty($loan_repay_list)){
            return true;
        }

        // 开始给一个用户还款
        $GLOBALS['db']->startTrans();
        try {

            $repayMoney = 0;
            foreach ($loan_repay_list as $loan_repay) {
                // 逐条变更回款记录状态
                $loan_repay->real_time = $realTime;
                $loan_repay->update_time = get_gmtime();
                $loan_repay->status = 1;
                if ($loan_repay->save() === false) {
                    throw new \Exception("变更{$loan_repay->id}回款记录状态失败");
                }

                switch ($loan_repay['type']) {
                    //本金
                    case self::MONEY_PRINCIPAL :
                        if ($loan_repay['money'] != 0) {
                            $repayMoney+=$loan_repay['money'];
                            $calInfo[$realTime][DealLoanRepayCalendarService::REPAY_PRINCIPAL] = $loan_repay['money']; // 真实还款日期本金增加
                            $calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_PRINCIPAL]-=$loan_repay['money']; // 原有日期本金减少


                            if(!isset($moneyInfo[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL])) {
                                $moneyInfo[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL] = 0;
                            }
                            if(!isset($calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_PRINCIPAL])) {
                                $calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_PRINCIPAL] = 0;
                            }

                            $moneyInfo[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] = $loan_repay['money'];
                            $moneyInfo[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL] = -$loan_repay['money'];
                        }
                        break;
                    //利息
                    case self::MONEY_INTREST :
                        // 智多鑫标的不变更回款日历
                        if(!isset($calInfo[$realTime][DealLoanRepayCalendarService::REPAY_INTEREST])) {
                            $calInfo[$realTime][DealLoanRepayCalendarService::REPAY_INTEREST] = 0;
                        }
                        if(!isset($calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_INTEREST])) {
                            $calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_INTEREST] = 0;
                        }
                        $calInfo[$realTime][DealLoanRepayCalendarService::REPAY_INTEREST] += $loan_repay['money']; // 真实还款日期利息增加
                        $calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_INTEREST]-=$loan_repay['money']; // 原还款日期本金减少

                        $repayMoney+=$loan_repay['money'];

                        $moneyInfo[UserLoanRepayStatisticsService::LOAD_EARNINGS] = $loan_repay['money'];
                        $moneyInfo[UserLoanRepayStatisticsService::NOREPAY_INTEREST] = -$loan_repay['money'];

                        if(!isset($moneyInfo[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY])) {
                            $moneyInfo[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] = 0;
                        }
                        $moneyInfo[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] += $loan_repay['money'];
                        break;
                    default:
                        continue;
                }
            }

            if(!empty($moneyInfo)) {
                if (UserLoanRepayStatisticsService::updateUserAssets($deal_loan_user_id,$moneyInfo) === false) {
                    throw new \Exception("user loan repay statistics error");
                }
            }

            if (!empty($calInfo)) {
                foreach($calInfo as $key=>$cinfo) {
                    $time = strtotime(to_date($key)); // 转为无差别时间
                    if (DealLoanRepayCalendarService::collect($deal_loan_user_id,$time,$cinfo,$time) === false) {
                        throw new \Exception("collect calendar error");
                    }
                }
            }

            $rs = $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw new \Exception($e->getMessage());
        }
        if ($rs === false) {
            throw new \Exception("事务提交失败");
        }

        // 发送站内信
        $user = UserModel::instance()->find($deal_loan_user_id);
        $dealRepayModel = DealRepayModel::instance()->find($deal_repay_id);
        $next_repay = $dealRepayModel->getNextRepay();
        $next_repay_id = $next_repay->id;
        DealLoanRepayModel::instance()->sendMsg($deal, $user, $deal_repay_id, $next_repay_id);

        return true;
    }
}