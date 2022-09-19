<?php
/**
 * @desc  掌众批量重新发起提前还款脚本
 * User: wangjiantong
 * Date: 2017/4/20 17:01
 */
require_once dirname(__FILE__).'/../app/init.php';


use core\service\DealService;
use core\service\DealRepayService;
use core\service\DealPrepayService;
use core\service\CouponDealService;


use app\models\dao\Deal;
use app\models\dao\DealLoanRepay;

use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\DealPrepayModel;
use core\dao\CouponDealModel;
use core\dao\UserModel;
use core\dao\JobsModel;

use core\dao\DealExtModel;

use libs\utils\Finance;


use core\dao\DealLoanRepayModel;


use core\dao\UserCarryModel;
use core\dao\DealLoanTypeModel;
use libs\utils\Logger;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$idsStr = isset($argv[1]) ? $argv[1] : '';
$endDay = isset($argv[2]) ? $argv[2] : date('Y-m-d');

if(empty($idsStr)){
    echo "未输入id!";
    exit();
}

$ids = explode(",",$idsStr);

$dealPrepayService = new DealPrepayService();
$dealService = new DealService();

foreach($ids as $dealId){
    //计算提前还款各项费用
    try{

        $dealInfo = $dealPrepayService->prepayCheck($dealId);
        $deal = $dealInfo['deal_base_info'];
        $deal['isDtb'] = 0;

        if($dealService->isDealDT($deal['id'])){
            $deal['isDtb'] = 1;
        }
        $dealExt = $dealInfo['deal_ext_info'];

        $res = calc($deal,$dealExt,$endDay);

        if(empty($res)){
            Logger::error($dealId.":计算金额失败!");
            echo $dealId.":计算金额失败!"."\n";
            continue;
        }

        $data = array(
            'deal_id'             => $dealId,
            'user_id'             => $res['user_id'],
            'prepay_time'         => $res['prepay_time'],
            'remain_days'         => $res['remain_days'],
            'prepay_money'        => $res['prepay_money'],
            'remain_principal'    => $res['remain_principal'],
            'prepay_interest'     => $res['prepay_interest'],
            'prepay_compensation' => $res['prepay_compensation'],
            'loan_fee'            => $res['loan_fee'],
            'consult_fee'         => $res['consult_fee'],
            'guarantee_fee'       => $res['guarantee_fee'],
            'pay_fee'             => $res['pay_fee'],
            'repay_type'          => 0, //借款人还款
            'pay_fee_remain'      => $res['pay_fee_remain'],
            'deal_type'           => $res['deal_type'],
        );

        if ($deal['isDtb'] == 1) {
            $data['management_fee'] = $res['management_fee'];
        }
        //$dealRepayService = new DealRepayService();

        try{
            $GLOBALS['db']->startTrans();
            $sql = "SELECT * FROM ".DB_PREFIX."deal_prepay WHERE deal_id= $dealId AND status =0";
            $res = $GLOBALS['db']->getRow($sql);

            if($res) {
                $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"UPDATE","id=".$res['id']);
                if ($res == false) {
                    throw new \Exception("insert deal_prepay error deal_id:".$dealId);
                }
            }else{
                $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"INSERT");
                if ($res == false) {
                    throw new \Exception("update deal_prepay error deal_id:".$dealId);
                }
            }

            $prepay = new DealPrepayModel();
            $prepay = $prepay->findBy("deal_id=".$dealId ." and status = 0");

            //发起提前还款
            // 标的优惠码设置信息
            $couponDealModel = new CouponDealModel();
            $dealCoupon = $couponDealModel->findBy("deal_id=".$dealId);

            if(!$dealCoupon) {
                throw new \Exception("优惠码设置信息获取失败deal_id:{$dealId}");
            }
            // 优惠码结算时间为放款时结算：直接保存计算后得出的各项数据
            // 优惠码结算时间为还清时结算： 保存结算后的各项数据 并修改优惠码返利天数
            if($dealCoupon['pay_type'] == 1) {
                $rebateDays = floor((get_gmtime() - $deal['repay_start_time'])/86400); // 优惠码返利天数=操作日期-放款日期

                if($rebateDays < 0) {
                    throw new \Exception("优惠码返利天数不能为负值:rebate_days:".$rebateDays);
                }
                // 更新优惠码返利天数
                $couponDealService = new CouponDealService();
                $couponRes = $couponDealService->updateRebateDaysByDealId($dealId, $rebateDays);;
                if(!$couponRes){
                    throw new \Exception("更新标优惠码返利天数失败");
                }
            }

            // 将标的置为还款中
            $res = $deal->changeRepayStatus(DealModel::DURING_REPAY);
            if ($res == false) {
                throw new \Exception("chage repay status error");
            }

            // 自动审核提前还款
            $prepay->status = 1;
            $prepay->save();

            // 用户资金冻结
            $dealDao = new Deal();
            // 还款总额 = 应还本金+应还利息+手续费+咨询费+担保费+支付服务费。
            // 若多投宝，还需加上管理服务费
            $prepayMoney = $prepay['prepay_money'];
            $prepayUserId = $dealService->getRepayUserAccount($dealId,$prepay->repay_type);
            $user = UserModel::instance()->find($prepayUserId);
            $user->changeMoneyDealType = $deal['deal_type'];
            $res = $user->changeMoney($prepayMoney, "提前还款", '编号'.$dealId, 0, 0, UserModel::TYPE_LOCK_MONEY);
            if(!$res) {
                throw new \Exception("用户提前还款资金冻结失败");
            }

            // 启动jobs进行还款操作
            $function  = '\core\service\DealPrepayService::prepay';
            $param = array('id' => $prepay->id, 'status' => $prepay->status, 'success' => 1, 'saveLogFile' => '', 'admInfo' => '','prepayUserId'=>$prepayUserId);

            $job_model = new JobsModel();
            $job_model->priority = 80;
            $job_model->addJob($function, array('param' => $param), false, 0);

            $GLOBALS['db']->commit();
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error($dealId.":自动提前还款失败!");
            echo $dealId.":自动提前还款失败!"."\n";
            throw $e;
        }


    }catch (\Exception $ex) {
        Logger::error($ex->getMessage());
        echo $ex->getMessage()."\n";
        continue;
    }
}

/**
 * 计算提前还款明细
 */
function calc($deal,$dealExt,$endDay) {
    if(!preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $endDay)) {
        throw new \Exception("结束日期{$endDay}格式不正确");
    }

    // 计算计息日期
    $dps = new DealRepayService();
    $interestTime =  $dps->getMaxRepayTimeByDealId($deal);
    // 因为$interest_time 有可能不是从零点开始记录的，所以计算天数会有误差
    $interestTime = to_timespan(to_date($interestTime,'Y-m-d')); // 转换为零点开始
    $endInterestTime = to_timespan($endDay); // 计息结束日期
    $remainDays = ceil(($endInterestTime - $interestTime)/86400); // 利息天数

    if($endInterestTime <= $interestTime) {
        return false;
    }

    $deal_loan_repay_model = new DealLoanRepay();
    $remain_principal = get_remain_principal($deal);
    $prepay_result = $deal_loan_repay_model->getPrepayMoney($deal['id'], $remain_principal, $remainDays);
    $prepay_money = $prepay_result['prepay_money']; // 还款总额
    $remain_principal = $prepay_result['principal']; // 应还本金
    $prepay_interest = $prepay_result['prepay_interest']; // 应还利息

    $deal_dao = new Deal();
    $remain_principal = $deal_dao->floorfix($remain_principal);
    $prepay_interest = $deal_dao->floorfix($prepay_interest);
    $prepay_compensation = $deal_dao->floorfix($prepay_money - $prepay_interest - $remain_principal);

    // 各项未收费用
    $deal_repay_model = new DealRepayModel();
    $fees = $deal_repay_model->getNoPayFees($deal,$dealExt,$endDay);

    // 开始计算回扣支付费用
    $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);
    if ($deal_ext['pay_fee_rate_type'] == 1) {
        $fee_days = ceil(($endInterestTime - $deal['repay_start_time'])/86400); // 利息天数
        $pay_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
        $pay_fee = DealModel::instance()->floorfix($deal['borrow_amount'] * $pay_fee_rate / 100.0);

        $pay_fee_rate_real = Finance::convertToPeriodRate(5, $deal['pay_fee_rate'], $fee_days, false);
        $pay_fee_real = DealModel::instance()->floorfix($deal['borrow_amount'] * $pay_fee_rate_real / 100.0);

        $pay_fee_remain = bcsub($pay_fee, $pay_fee_real, 2);
    }

    $data = array(
        'deal_id'             => $deal['id'],
        'user_id'             => $deal['user_id'],
        'interest_time'       => $interestTime, // 计息日期
        'prepay_time'         => $endInterestTime, // 提前还款日期
        'remain_days'         => $remainDays, // 利息天数
        'remain_principal'    => $remain_principal,
        'prepay_interest'     => $prepay_interest,
        'prepay_compensation' => $prepay_compensation,
        'loan_fee'            => $fees['loan_fee'],
        'consult_fee'         => $fees['consult_fee'],
        'guarantee_fee'       => $fees['guarantee_fee'],
        'pay_fee'             => $fees['pay_fee'],
        'pay_fee_remain'      => !empty($pay_fee_remain) ? $pay_fee_remain : 0,
        'deal_type'           => $deal['deal_type'],
    );

    $managementFee = 0;
    if ($deal['isDtb'] == 1) {
        $data['management_fee'] = $fees['management_fee'];
        $managementFee = $fees['management_fee'];
    }

    $prepay_money = $deal_dao->floorfix($prepay_money + $fees['loan_fee'] + $fees['consult_fee'] + $fees['guarantee_fee'] + $fees['pay_fee'] + $managementFee);
    $data['prepay_money'] = $prepay_money;

    return $data;
}
