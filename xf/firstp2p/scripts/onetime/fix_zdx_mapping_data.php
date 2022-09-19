<?php

/*
 * 修复用户130801资金及资金记录问题
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';

use \libs\Db\Db;
use core\dao\DealModel;
use core\dao\IdempotentModel;
use libs\utils\Logger;
use core\service\DtPaymenyService;
use core\service\DtDepositoryService;
use NCFGroup\Common\Library\Idworker;

set_time_limit(0);
ini_set('memory_limit', '4096M');

if(!isset($argv[1])){
    die("参数minId错误");
}
if(!isset($argv[1])){
   die("参数maxId错误");
}
if(isset($argv[3])){
   update_to_succ();
   exit;
}

$minId = $argv[1];
$maxId = $argv[2];


function update_to_succ(){
    $sql = "update  `firstp2p_idempotent`set status = 2  WHERE source='duotou_depository_redeem' AND status !=2 AND create_time < 1523759411";
    $res =  IdempotentModel::instance()->updateRows($sql);
}



$sql = "SELECT * FROM `firstp2p_idempotent` WHERE STATUS =2 AND source='duotou_depository_redeem' AND create_time > 1523721600 and create_time < 1523812719 AND id >=".$minId." AND id < ".$maxId;

$result = IdempotentModel::instance()->findAllBySqlViaSlave($sql,true);

$service = new DtDepositoryService();

foreach($result as $k=>$v){
   $id = $v['id'];
   $data = json_decode($v['data'],true);

   if(in_array($v['token'],array('170117616541638792','170117616545833929'))){
       continue;
   }

   $batchId = Idworker::instance()->getId();
   $subId = Idworker::instance()->getId();

    $creditOrderList = array(
        "bidId" => $data['dealId'],
        "subOrderId" => $subId,
        "assignorUserId" => $data['userId'],
        "assigneeUserId" => $data['redeemUserId'],
        "amount" => $data['money'],
        "dealAmount" => $data['money'],
        "freezeType" => "SI",
    );

$requestData = array(
    "totalAmount" =>  $data['money'],
    "dealTotalAmount" => $data['money'],
    "totalNum" => 1,
    "creditOrderList" => array($creditOrderList),
);

    $tableIndex =  $data['tableIndex'];
    $date = $data['date'];
    try{
        $res = $service->sendDtTransBondRequest($batchId,$requestData,$tableIndex,$date);
    
    }catch(\Exception $ex){
        $res = false;
    }
    if(!$res){
        Logger::error("fix_zdx_mapping_data fail id:{$id}, batchId:{$batchId},subId:{$subId}");
        break;
    }else{
        Logger::error("fix_zdx_mapping_data succ id:{$id}, batchId:{$batchId},subId:{$subId}");
    }
}
