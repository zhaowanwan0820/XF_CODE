<?php
/**
 * 补发合同
 */
require_once dirname(__FILE__) . '/../app/init.php';

use core\dao\DealModel;
use core\service\ContractService;

set_time_limit(0);
ini_set('memory_limit', '1024M');
if ( count($argv)!=4 ){
    echo '`which php` contract_batch_repair.php ${startId} ${endId}  ${reNewFlag|new,no}'."\n"; 
    exit(0);
}

$startId = intval($argv[1]);
$endId = intval($argv[2]);
$reNewFlag = isset($argv[3]) ? $argv[3] : 'no';


function contractRenew($dealId,$id){
    $contract_service = new ContractService();
    $res = $contract_service->contractRenew($dealId, '', array($id));
    if($res['num'] > 0){
        echo sprintf("contract reNewSucc [id:%s] \n",$id);
    }else{
        echo sprintf("contract reNewSucc failed  [id:%s] \n",$id);
    }
}

//69965105 - 72983749

$contract_service = new ContractService();
//22号时间戳1434902400
$count = 0;
for($id=$startId;$id<$endId;$id++){
   $contract = $contract_service->getContract($id, $need_content = true);
   $contractContent = $contract['content'];
   $dealId = $contract['deal_id'];
   if(empty($contractContent)){
       $count ++;
       echo sprintf("contract is empty [id:%s ,deal_id:%s ,title:%s] \n",$id,$contract['deal_id'],$contract['title']);
       if($reNewFlag === 'new'){
           contractRenew($dealId,$id);
       }
   }
}
echo sprintf("empty contract content number is $count \n",$id);
