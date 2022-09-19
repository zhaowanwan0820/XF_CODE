<?php
/**
 * @desc  合同打戳批作业控制台 每15分钟跑一次
 * User: jinhaidong
 * Date: 2016/2/2 14:51
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\JobsModel;
use core\dao\DealModel;
use libs\utils\Rpc;

use core\service\ContractInvokerService;

use libs\utils\Logger;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use NCFGroup\Protos\Contract\RequestGetDealTsaInfo;

$date = $argv[1];

if(empty($date)){
   echo "参数错误!";
   exit();
}


$startTime = to_timespan($date . " 00:00:00");
$endTime = to_timespan($date . " 23:59:59");

$sql = "SELECT id,deal_type,project_id FROM `firstp2p_deal` where repay_start_time >= ".$startTime." AND repay_start_time<=".$endTime." AND deal_status IN (4,5)";
echo $sql."\n";
$rows = $GLOBALS['db']->get_slave()->getAll($sql);
$projects = array();

foreach($rows as $deal) {
    try{
        $projects[$deal['project_id']] = $deal['project_id'];

        $rpc = new Rpc('contractRpc');

        $dealInfo = DealModel::instance()->find($deal['id']);
        $contractRequest = new RequestGetDealTsaInfo();
        $contractRequest->setDealId(intval($deal['id']));
        $contractRequest->setSourceType($dealInfo['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getDealTsaInfo",$contractRequest);
        if($response->errCode == 0){
            if(($response->data['total'] - $response->data['signed']) > 0) {
                if (!ContractInvokerService::signAllContractByServiceId('signer', $deal['id'])) {
                    echo "Deal_id :" . $deal['id'] . " fail!\n";
                } else {
                    echo "Deal_id :" . $deal['id'] . " success!\n";
                }
            }
        }else{
            echo "Contract Rpc Faild :".$deal['id']."\n";
        }

    }catch (\Exception $ex) {
        continue;
    }
}

$dealProjectService = new \core\service\DealProjectService();

foreach($projects as $projectId){
    try {
        if ($dealProjectService->isProjectEntrustZX($projectId)) {
            if (!ContractInvokerService::signAllContractByServiceId('signer', $projectId, ContractServiceEnum::SERVICE_TYPE_PROJECT)) {
                echo "Project_id :" . $projectId . " fail!\n";
            } else {
                echo "Project_id :" . $projectId . " success!\n";
            }
        }
    }catch (\Exception $ex) {
        Logger::error("BatchJobsTs | run | fail ".$ex->getMessage());
        continue;
    }
}
