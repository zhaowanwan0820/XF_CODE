<?php
/**
 * 修复提前还款取消导致的用户回款日历变为负值的问题
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\service\DtBidService;
use core\dao\IdempotentModel;
use libs\utils\Logger;
use core\service\DtPaymenyService;
use core\service\DtDepositoryService;
use core\service\DtDealService;
use NCFGroup\Common\Library\Idworker;
use core\dao\UserThirdBalanceModel;
use core\service\BonusService;

$method = isset($argv[1]) ? trim($argv[1]) : 0;
if(!$method){
    exit;
}

$method();


//
function zdx_bank_bid_redo(){
    $data = array(
        'orderId' => 177423730123743538,// 老单 177067285456888557,
        'userId' => 10246321,
        'freezeType'    => '01',
        'freezeSumAmount'   => 6090549,
        'freezeAccountAmount'  => 0,
        'mobileType' => 11,
    );

    $bonusData =  array (
        array ( 'rpUserId' => '8229699', 'rpAmount' => '249503', 'rpSubOrderId' => 177423871572447509, ),
        array ( 'rpUserId' => '9025215', 'rpAmount' => '5841000', 'rpSubOrderId' => 177423900223738628, ),
        array ( 'rpUserId' => '7091228', 'rpAmount' => '46', 'rpSubOrderId' => 177423923581821204, ), );
    $data['rpOrderList'] = json_encode($bonusData);

    $bidService = new DtPaymenyService();
    $res = $bidService->bookfreezeCreate($data);
    var_dump($res);
}

// 地城资产匹配
function zdx_bid_mapping1(){
    $orderId = 177426068032987447;//177223676142298019;
    $userId = 10246321;
    $dealId = 5479053;
    $money = 49864.26;
    $otherBidParams = array(
        'tableIndex' => 1,
        'contractId' => 603311
    );
    $s = new DtDepositoryService();
    $res = $s->sendDtBidRequest($orderId,$userId,$dealId,$money,$otherBidParams);
    var_dump($res);
}

// 地城资产匹配
function zdx_bid_mapping2(){
    $orderId = 177426147095618263;//177223676154876588;
    $userId = 10246321;
    $dealId = 5479055;
    $money = 11041.23;
    $otherBidParams = array(
        'tableIndex' => 1,
        'contractId' => 603312
    );
    $s = new DtDepositoryService();
    $res = $s->sendDtBidRequest($orderId,$userId,$dealId,$money,$otherBidParams);
    var_dump($res);
}



function update_order_status(){
    $sql = "update `firstp2p_supervision_idempotent`  set status=1,result=0 WHERE order_id=177067285456888557";
    $res = \core\dao\SupervisionIdempotentModel::instance()->execute($sql);
    var_dump("数据库更新结果".$res);
}

function update_bid_order_succ(){
    $sql = "update `firstp2p_supervision_idempotent`  set result=1 WHERE order_id in (177223676142298019,177223676154876588)";
    $res = \core\dao\SupervisionIdempotentModel::instance()->execute($sql);
    var_dump("数据库更新结果".$res);
}

function zdx_consume_bonus(){
    $bidOrderId = 177067285456888557;
    $bs = new BonusService();
    $bonRes = $bs->consumeConfirmBonus($bidOrderId);

    if($bonRes) {
        echo "红包消费成功 orderId:{$bidOrderId}\n";
    }else{
        echo "红包消费失败 orderId:{$bidOrderId}\n";
    }
}

function zdx_bid_redo(){
    try{
        $s = new \core\service\DtBidService();
        $orderId = '177067285456888557';
        $dealId = '1004';
        $userId = 10246321;
        $dtDealName='智多鑫';
        $money = '60905.49';
        $bidParams = array(
            'couponId' => 0,
            'bonusInfo' => array (
                'money' => '60905.49',
                'bonuses' => array ( ),
                'accountInfo' => array (
                    0 => array ( 'rpUserId' => '8229699', 'rpAmount' => '2495.03', 'rpSubOrderId' => 177067293627388679, ),
                    1 => array ( 'rpUserId' => '9025215', 'rpAmount' => '58410.00', 'rpSubOrderId' => 177067293648359660, ),
                    2 => array ( 'rpUserId' => '7091228', 'rpAmount' => '0.46', 'rpSubOrderId' => 177067293652554682, ), ), ),
            'activityId' => 1,
            'siteId' => '1',
            'discount_id' => '',
            'discount_type' => '',
            'activityRate' => '5.00',
            'lockPeriod' => '1',
            'minInvestMoney' => '500.00',
            'isNewUser' => 1,
        );
        $bidRes = $s->dtBid($orderId,$userId,$dealId,$dtDealName,$money,$bidParams);
        var_dump($bidRes);
    }catch (\Exception $ex){
        var_dump($ex->getMessage());
    }
}






