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

$ids =  "988143,
988145,
988146,
988147,
988148,
988149,
988150,
988151,
988152,
988153,
988154,
988155,
988156,
988157,
988158,
988159,
988160,
988161,
988162,
988164,
988366,
988367,
988368,
988369,
988370,
988371,
988372,
988374,
988375,
988376,
989090,
989091,
989092,
989103,
989509,
989510,
989511,
989512,
990758
";
$sql = "SELECT * FROM firstp2p_idempotent where id in (".$ids.")";
$result =  IdempotentModel::instance()->findAllBySqlViaSlave($sql,true);
 $service = new DtDepositoryService();
foreach($result as $k=>$v){
   $data = json_decode($v['data'],true);

   $batchId = Idworker::instance()->getId();
   $subId = Idworker::instance()->getId();

    $creditOrderList = array(
        "bidId" => $data['dealId'],
        "subOrderId" => $subId,
        "assignorUserId" => $data['redeemUserId'],
        "assigneeUserId" => $data['userId'],
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
