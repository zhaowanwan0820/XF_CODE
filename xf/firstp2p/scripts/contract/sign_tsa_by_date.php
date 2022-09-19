<?php
/**
 * 按日期范围打指定时间范围内的戳
 * User: wangjiantong
 * Date: 2018/1/2 11:47
 */

require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

use core\dao\DealModel;
use core\service\ContractNewService;
use core\service\ContractInvokerService;
use libs\utils\Logger;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
set_time_limit(0);
ini_set('display_errors' , 1);
ini_set('memory_limit', '1024M');

if(isset($argv[1]) && isset($argv[2])){
    $startDate = $argv[1];
    $endDate = $argv[2];
}else{
    echo "date fail! \n";
    exit();
}

$startTime = to_timespan($startDate . "00:00:00");
$endTime = to_timespan($endDate . "00:00:00");

$sql = "SELECT id,deal_type,entrust_agency_id,project_id FROM `firstp2p_deal` where repay_start_time >=".$startTime." AND repay_start_time < ".$endTime." AND deal_status IN (4,5)";
$rows = $GLOBALS['db']->get_slave()->getAll($sql);

$projects = array();
$redis = \SiteApp::init()->dataCache->getRedisInstance();
foreach($rows as $deal) {
    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"开始打戳begin deal_id:".$deal['id'])));
    try{
        $projects[$deal['project_id']] = $deal['project_id'];
        if ($redis !== NULL){
            $redis->hSet('tsa_deal_'.date('Y-m-d'), $deal['id'], false);
        }
        if(!ContractInvokerService::signAllContractByServiceId('signer', $deal['id'])) {
            throw new \Exception("打戳失败 deal_id".$deal['id']);
        }
    }catch (\Exception $ex) {
        Logger::error("Sign deal error | run | fail ".$ex->getMessage());
        continue;
    }
    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"成功打戳success deal_id:".$deal['id'])));
}

/** 同步签署项目合同*/
$dealProjectService = new \core\service\DealProjectService();

foreach($projects as $projectId){
    try{
        if ($dealProjectService->isProjectEntrustZX($projectId)) {
            if(!ContractInvokerService::signAllContractByServiceId('signer', $projectId, ContractServiceEnum::SERVICE_TYPE_PROJECT)) {
                throw new \Exception("项目合同打戳失败 project_id".$projectId);
            }
        }
    }catch (\Exception $ex) {
        Logger::error("Sign project error | run | fail ".$ex->getMessage());
        continue;
    }
    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"成功打戳success project_id:".$projectId)));

}