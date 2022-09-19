<?php

/*
 * 修复功夫贷标的还款计划
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';

use core\dao\DealRepayModel;
use core\dao\DealModel;
use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '4096M');

$dealIds = array(5429436,5429340,5429631,5429618,5429379,5429365,5429634,5429810,5429346,5429348,5429371,5429610,5429364,5429835,5429905,5429404,5429380,5429465,5429362,5429503,5429369,5429368,5429593,5429399,5429383,5429410,5429817,5429406,5429811,5429388,5429430,5429425,5429450,5429454,5429478,5429462,5429463,5429469,5429816,5429517,5429526,5429575,5429540,5429562,5429566,5429568,5429843,5429585,5429594,5429616,5429620,5429621,5429623,5429624,5429629,5429799,5429638,5429641,5429652,5429798,5429657,5429659,5429894,5429665,5429682,5429689,5429692,5429703,5429791,5429704,5429711,5429784,5429719,5429721,5429722,5429769,5429733,5429746,5429755,5429762,5429766,5429772,5429853,5429779,5429788,5429844,5429858,5429861,5429871,5429875,5429876,5429886,5429895,5429896,5429909,5429911,5429935,5429943,5429945,5429946,5429950,5429964,5429954,5429967,5429969,5429974,5429982,5429997,5429999,5430013,5430018,5430028,5430066,5430045,5430047,5430048,5430049,5430055,5430058,5430073,5430081,5430086);

$dealRepayModel = new DealRepayModel();

foreach($dealIds as $dealId){
    $deal = DealModel::instance()->find($dealId);
    $consultFeePeriodRate = $deal->consult_fee_period_rate;

    $dealRepays = $dealRepayModel->findAllBySql("SELECT * FROM firstp2p_deal_repay WHERE deal_id = ".$dealId." AND consult_fee = 0;");
    if(count($dealRepays > 0)){
        foreach($dealRepays as $dealRepay){
            $GLOBALS['db']->startTrans();
            try {
                $dealRepay->consult_fee += floorfix($deal['borrow_amount'] * $deal['consult_fee_period_rate'] / 100.0);
                $dealRepay->repay_money += floorfix($deal['borrow_amount'] * $deal['consult_fee_period_rate'] / 100.0);
                if(!$dealRepay->save()){
                    throw new \Exception("保存失败!");
                }else{
                    echo "还款计划:$dealRepay->id,修正成功! \n";
                    Logger::info("还款计划:$dealRepay->id,修正成功!");
                }
                $GLOBALS['db']->commit();

            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                echo "标的:$deal->id,修正失败!".$e->getMessage()."\n";
                Logger::info("标的:$deal->id,修正失败!".$e->getMessage());
            }
        }
    }

    echo "标的:$deal->id,修正成功! \n";
    Logger::info("标的:$deal->id,修正成功!");
}