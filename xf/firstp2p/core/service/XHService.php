<?php
/**
 * 云图-享花service
 * @author jinhaidong@ncfgroup.com
 * @date 2017-7-27 16:10:17
 */

namespace core\service;

use core\dao\DealLoanRepayModel;
use core\service\DealService;
use core\service\partner\RequestService;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\LoanThirdMapModel;
use core\dao\DealPrepayModel;
use core\dao\DealRepayModel;
use libs\utils\Alarm;
use libs\utils\Logger;
use core\dao\JobsModel;


class XHService extends BaseService {

    const TAG_XH = 'HHNZ_XH'; // 享花标的TAG

    const REPAY_TYPE_NORMAL = 1; // 正常还款
    const REPAY_TYPE_PREPAY = 2; // 提前还款
    const REPAY_TYPE_FAIL = 3; // 流标

    /**
     * 享花标的在投资成功之后通知享花
     * @param $loadId
     */
    public function bidSuccessNotify($loadId){
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",投资信息通知享花 loadId:{$loadId}");
        $loadInfo = DealLoadModel::instance()->find($loadId);
        if(empty($loadInfo)){
            throw new \Exception("投资信息不存在 loadId:{$loadId}");
        }

        $earning = (new \core\service\EarningService())->getEarningMoney($loadInfo->deal_id,$loadInfo->money);

        $params = array(
            'open_id' => $loadInfo->user_id,
            'deal_id' => $loadInfo->deal_id,
            'load_id' => $loadId,
            'deal_name' => $loadInfo->user_deal_name,
            'money' => $loadInfo->money,
            'income' => $earning,//预期收益
            'create_time' => $loadInfo->create_time+28800,
        );

        // 通知享花
        $xhRes = RequestService::init('xianghua')->setApi('invest.notify')->setPost($params)->request();
        if($xhRes['errorCode'] == 0){
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",投资信息通知享花成功 params:".json_encode($params));
            return true;
        }
        throw new \Exception("投资信息通知享花失败");
    }

    /**
     * 享花标的投资信息查询
     * @param $loadId
     */
    public function bidInfoSearch($loadId,$userId){
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",享花投资信息查询 loadId:".$loadId);
        $s = new \core\service\DealLoadService();
        $loadInfo = $s->getDealLoadDetail($loadId,true,true);
        if(!$loadInfo){
            throw new \Exception("投资信息不存在 loadId:{$loadId}");
        }
        if($loadInfo->user_id != $userId){
            throw new \Exception("投资信息与用户不符");
        }

        $deal = $loadInfo->deal;
        $params = array(
            'load_id' => $loadInfo->id,
            'load_time' => $loadInfo->create_time+28800,
            'deal_id' => $loadInfo->deal_id,
            'deal_name' => $deal['name'],
            'deal_status' => $deal['deal_status'],
            'money' => $loadInfo['money'],
            'income' => bcsub($loadInfo['total_income'],$loadInfo['money'],2),
            'loan_type' => $deal['loantype'],
            'rate' => $deal->rate,
            'repay_start_time' => !empty($deal['repay_start_time']) ? $deal['repay_start_time']+28800 : '',
            'next_repay_time' => !empty($deal['next_repay_time']) ? $deal['next_repay_time']+28800 : '',
        );

        Logger::info(__CLASS__ . ",". __FUNCTION__ .",享花投资信息查询成功 params:".json_encode($params));
        return $params;
    }


    /**
     * 享花标的还款 通知享花
     * @param $repayId 还款 or 提前还款ID
     * @param $repayType repay or prepay
     * @throws \Exception
     */
    public function repaySuccessNotify($dealId,$repayId,$repayType){
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",享花还款通知 repayId:{$repayId}");

        $dealService = new DealService();
        $dealInfo = $dealService->getDeal($dealId);
        if(!$dealInfo){
            throw new \Exception("标的信息不存在");
        }

        $loanRepayList = DealLoanRepayModel::instance()->getListByRepayId($dealId,$repayId);
        if(!$loanRepayList){
            throw new \Exception("回款信息不存在");
        }
        if($repayType == self::REPAY_TYPE_PREPAY){
            $isLastRepay = 1;
        }else{
            $nextRepay = DealRepayModel::instance()->getNextRepayByRepayId($dealId,$repayId);
            $isLastRepay = $nextRepay ? 0 : 1;
        }

        try {
            $GLOBALS['db']->startTrans();
            foreach($loanRepayList as $loanId => $loanRepayInfo){

                $out_order_id = LoanThirdMapModel::instance()->addNxLoanThirdMap($loanRepayInfo['loan_user_id'], $dealId, $repayId, $repayType);
                if(!$out_order_id){
                    throw new \Exception("保存还款订单信息失败");
                }

                if(isset($loanRepayInfo['principal'])){
                    $principal = $loanRepayInfo['principal'];
                }elseif(isset($loanRepayInfo['prepay'])){
                    $principal = $loanRepayInfo['prepay'];
                }else{
                    $principal = 0;
                }
                $interest = 0;
                $interest+=isset($loanRepayInfo['intrest']) ? $loanRepayInfo['intrest']:0;
                $interest+=isset($loanRepayInfo['compensation']) ? $loanRepayInfo['compensation']:0;
                $interest+=isset($loanRepayInfo['impose']) ? $loanRepayInfo['impose']:0;
                $interest+=isset($loanRepayInfo['prepayIntrest']) ? $loanRepayInfo['prepayIntrest']:0;

                $params = array(
                    'load_id' => $loanId,
                    'open_id' => $loanRepayInfo['loan_user_id'],
                    'deal_id' => $dealId,
                    'repay_id' => $repayId,
                    'principal' => $principal,
                    'interest' => $interest,
                    'is_last' => $isLastRepay,
                    'real_time' => $loanRepayInfo['real_time']+28800,
                    'out_order_id' => $out_order_id,
                );

                $jobs_model = new JobsModel();
                $function = '\core\service\XHService::repaySuccessNotifyOne';
                $jobs_model->priority = JobsModel::PRIORITY_XH_REPAY_NOTIFY;
                $r = $jobs_model->addJob($function, array('param' => $params), false, 5);
                if ($r === false) {
                    throw new \Exception('add XHService::repaySuccessNotifyOne error');
                }
            }

            $GLOBALS['db']->commit();
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",享花还款通知加入JOBS成功 repayId:{$repayId},params:".json_encode($params));

        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",享花还款通知失败 errMsg:".$e->getMessage());
            throw new \Exception($e->getMessage());
        }
        return true;
    }


    /**
     * 流标通知享花
     * @param $dealId
     */
    public function failSuccessNotify($dealId,$userId,$loadId,$money){
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",享花流标通知 dealId:{$dealId} money:{$money}");
        try{
            $GLOBALS['db']->startTrans();
            $out_order_id = LoanThirdMapModel::instance()->addNxLoanThirdMap($userId, $dealId, 0, self::REPAY_TYPE_FAIL);
            if(!$out_order_id){
                throw new \Exception("保存还款订单信息失败");
            }

            $params = array(
                'load_id' => $loadId,
                'open_id' => $userId,
                'deal_id' => $dealId,
                'repay_id' => 0,
                'principal' => $money,
                'interest' => 0,
                'is_last' => 1,
                'real_time' => time(),
                'out_order_id' => $out_order_id,
            );

            $jobs_model = new JobsModel();
            $function = '\core\service\XHService::repaySuccessNotifyOne';
            $jobs_model->priority = JobsModel::PRIORITY_XH_REPAY_NOTIFY;
            $r = $jobs_model->addJob($function, array('param' => $params), false, 5);
            if ($r === false) {
                throw new \Exception('add XHService::repaySuccessNotifyOne error');
            }
            $GLOBALS['db']->commit();
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",享花流标通知加入JOBS成功 dealId:{$dealId},params:".json_encode($params));
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",享花流标通知失败 errMsg:".$e->getMessage());
            throw new \Exception($e->getMessage());
        }
        return true;
    }


    /**
     * 还款通知享花
     * @param $params
     */
    public function repaySuccessNotifyOne($params){
        // 还款通知享花
        $xhRes = RequestService::init('xianghua')->setApi('refund.notify')->setPost($params)->request();
        if($xhRes['errorCode'] == 0){
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",还款信息通知享花成功 params:".json_encode($params));
            return true;
        }
        throw new \Exception("还款信息通知享花失败");
    }


    /**
     * 获取享花标的最大的还款金额--按照投资ID汇总的
     * @param $dealId
     * @param $repayId
     * @param $userId
     * @return array
     */
    public function getXHRepayMoneyInfo($dealId,$repayId,$userId,$repayType){
        $total = $principal = $interest = 0;
        if($repayType == self::REPAY_TYPE_FAIL){ // 流标
            $principal = DealLoadModel::instance()->getUserTotalMoneyByDeal($userId,$dealId);
            $total = $principal;
        }else{
            $res = DealLoanRepayModel::instance()->getOneByUserId($dealId,$repayId,$userId);
            $interest= isset($res['intrest']) ? bcadd($interest,$res['intrest'],2) : $interest;
            $interest= isset($res['compensation']) ? bcadd($interest,$res['compensation'],2) : $interest;
            $interest= isset($res['impose']) ? bcadd($interest,$res['impose'],2) : $interest;
            $interest= isset($res['prepayIntrest']) ? bcadd($interest,$res['prepayIntrest'],2) : $interest;

            $principal= isset($res['principal']) ? bcadd($principal,$res['principal'],2) : $principal;
            $principal= isset($res['prepay']) ? bcadd($principal,$res['prepay'],2) : $principal;
            $total = bcadd($principal,$interest,2);
        }

        $res = array('total' => $total,'principal'=>$principal,'interest' => $interest);
        return $res;
    }
}