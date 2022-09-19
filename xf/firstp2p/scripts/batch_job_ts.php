<?php
/**
 * @desc  合同打戳批作业控制台 每15分钟跑一次
 * User: jinhaidong
 * Date: 2016/2/2 14:51
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\JobsModel;
use core\dao\DealModel;

use core\service\ContractInvokerService;

use libs\utils\Logger;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

class BatchJobTs {

    public function run() {
        $begin15 = time()-900; // 取最近15分钟
        $end15 = time();

        $isRun = $this->HasNotFinishTsBatchJob($begin15,$end15);
        if(!$isRun) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"没有需要执行的打戳批作业startTime:{$begin15}")));
            exit();
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"打戳批作业任务开始执行")));

        $startTime = to_timespan(date("Y-m-d") . "00:00:00") - 86400;

        $deals = $this->getYesterdayStartDeals($startTime); // 取得昨日放款标的

        if(empty($deals)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"昨日放款标的为空 startTime:{$startTime}")));
            exit;
        }

        /** 调用合同打戳服务 未避免合同服务压力过大 每调用后sleep 3s */
        $projects = array();

        $redis = \SiteApp::init()->dataCache->getRedisInstance();

        foreach($deals as $deal) {
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
                Logger::error("BatchJobsTs | run | fail ".$ex->getMessage());
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
                Logger::error("BatchJobsTs | run | fail ".$ex->getMessage());
                continue;
            }

        }
    }

    /**
     * 是否有未完成的批作业
     */
    private function HasNotFinishTsBatchJob($startTime,$endTime) {
        $sql = "SELECT * FROM `firstp2p_batch_job`  WHERE job_status=1 AND job_type=2 AND job_interval_start <=$endTime AND job_interval_end >=$startTime";
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        $isRun = false;
        foreach($rows as $row) {
            $runTime = date('Y-m-d')." ".$row['job_run_time'];
            $runTime = strtotime($runTime);
            if($runTime >= $startTime && $runTime <=$endTime) {
                $isRun = true;
                break;
            }
        }
        return $isRun;
    }

    /**
     * 取得昨日放款的标的
     */
    private function getYesterdayStartDeals($startTime) {
        $sql = "SELECT id,deal_type,entrust_agency_id,project_id FROM `firstp2p_deal` where repay_start_time =".$startTime." AND deal_status IN (4,5)";
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        return $rows;
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');


$obj = new BatchJobTs();
$obj->run();

