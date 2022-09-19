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


$res = file("/tmp/567");

$uidArr = array();
$s = new \core\service\DtPaymenyService();

$isOnleyCheck = isset($argv[1]) ? false : true;


foreach($res as $item){
    $data = explode("\t",$item);
    $orderId = $data[1];
    $uid = $data[2];
    $money = $data[3];

    $params = array(
        'orderId' => Idworker::instance()->getId(),
        'userId' => $uid,
        'unFreezeType' => '01',
        'amount' => $money,
    );

    if(in_array($uid,array(11284926,10587974,9470469))){
        continue;
    }


   // $checkRes = check($uid,$money,$orderId);
   // if($checkRes === false){
    //    continue;
    //}

    if($isOnleyCheck === false){
        try{
            $res = $s->bookfreezeCancel($params);
            if($res['status'] == \core\service\SupervisionBaseService::RESPONSE_SUCCESS) {
                Logger::info("zdx_redeem cancel lock succ params:".json_encode($params));
            }else{
                Logger::info("zdx_redeem cancel lock fail params:".json_encode($params));
            }
        }catch (\Exception $ex){
            Logger::error("zdx_redeem cancel lock fail params:".json_encode($params)." err:".$ex->getMessage());
        }
    }
}

function check($uid,$money,$orderId){

    $superAccountService = new \core\service\SupervisionAccountService();
    $bankRes = $superAccountService->balanceSearch($uid);
    $bankMoney = $supervisionBalance['data']['freezeBalance'];

    $res = UserThirdBalanceModel::instance()->getUserThirdBalance($uid);
    $slock = bcmul($res['supervisionLockMoney'],100);
    if(($bankMoney-$money) == $slock){
        Logger::info("zdx_redeem money check succ orderId:{$orderId},uid:{$uid}, money:".$money);
        return true;
    }else{
        Logger::info("zdx_redeem money check fail orderId:{$orderId},uid:{$uid}, money:".$money." slock:".$slock);
        return false;
    }
}
