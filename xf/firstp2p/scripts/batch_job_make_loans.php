<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 16/5/31
 * Time: 下午6:42
 */


require_once dirname(__FILE__).'/../app/init.php';

\FP::import("app.deal");

use core\service\DealService;
use core\service\DealProjectService;
use core\dao\JobsModel;
use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\LoanOplogModel;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;

class BatchJobMakeLoans
{

    public function run()
    {
        $begin15 = time() - 900; //取最近15分钟
        $end15 = time();

        $dealTypes = $this->GetData($begin15, $end15);
        $dealModel = new DealModel();
        $dealService = new DealService;

        //获取满标有效且业务类型为指定放款类型的标的列表
        if(count($dealTypes['records']) > 0){
            foreach($dealTypes['records'] as $record){
                $successTime = date('Y-m-d') . " " . $record['full_status_time'];
                $successTime = to_timespan($successTime);
                if($record['deal_type'] == 0){
                    $dealIds = $dealModel->findAll("is_effect = 1 AND deal_status = 2 AND success_time < ".$successTime,true,'id');
                    $makeLoanDeals = $dealIds;
                    break;
                }else{
                    $dealIds = $dealModel->findAll("is_effect = 1 AND deal_status = 2 AND type_id = ".$record['deal_type']." AND success_time < ".$successTime,true,'id');
                    foreach($dealIds as $dealId){
                        $makeLoanDeals[] = $dealId;
                    }
                }
            }
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "没有需要执行的放款批作业startTime:{$begin15},endTime:{$end15}")));
            exit();
        }

        //执行放款操作
        $dealModel = new DealModel();
        $deal_pro_service = new DealProjectService();
        $db = $GLOBALS['db'];
        if(count($makeLoanDeals) > 0){
            foreach($makeLoanDeals as $deal){
                if($this->CheckDeal($deal['id'])){
                    $deal = $dealModel->find($deal['id']);
                    //过滤专享1.75标的
                    if($deal_pro_service->isProjectEntrustZX($deal['project_id'])){
                        continue;
                    }

                    $db->startTrans();
                    try{
                        //放款添加到jobs
                        if(!$dealService->isP2pPath(intval($deal['id']))) {
                            // 添加jobs
                            $function = '\core\service\DealService::makeDealLoansJob';
                            $param = array('deal_id' => intval($deal['id']), 'admin' => '', 'submit_uid' => 0);
                        }else{
                            $orderId = Idworker::instance()->getId();
                            $function = '\core\service\P2pDealGrantService::dealGrantRequest';
                            $param = array(
                                'orderId' => $orderId,
                                'dealId'=>$deal['id'],
                                'param'=>array('deal_id' => $deal['id'], 'admin' => '', 'submit_uid' => 0),
                            );
                            Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$orderId." dealId:".$deal['id']);
                        }
                        //设置标的为还款中状态
                        $repayStartTime = $deal['repay_start_time'] == 0?to_timespan(date("Y-m-d")):$deal['repay_start_time'];
                        if(intval($deal['next_repay_time']) == 0) {
                            $delta_month_time = get_delta_month_time($deal['loantype'], $deal['repay_time']);
                            // 按天一次到期
                            if ($deal['loantype'] == 5) {
                                $nextRepayTime = next_replay_day_with_delta($deal['repay_start_time'], $delta_month_time);
                            } else {
                                $nextRepayTime = next_replay_month_with_delta($deal['repay_start_time'], $delta_month_time);
                            }
                        }else {
                            $nextRepayTime = 0;
                        }

                        $updateSql = "UPDATE firstp2p_deal SET deal_status = 4,repay_start_time = ".$repayStartTime." ,next_repay_time = ".$nextRepayTime." WHERE id = ".$deal['id'];
                        if($dealModel->execute($updateSql)){
                            syn_deal_status($deal['id']);
                            syn_deal_match($deal['id']);

                            $deal_pro_service->updateProBorrowed($deal['project_id']);
                            $deal_pro_service->updateProLoaned($deal['project_id']);

                            $job_model = new JobsModel();
                            $job_model->priority = 99;
                            $add_job = $job_model->addJob($function, $param, get_gmtime() + 180);

                            if (!$add_job) {
                                throw new \Exception("放款任务添加失败");
                            }

                            //更新标放款状态
                            $save_status = $dealModel->changeLoansStatus($deal['id'], 2);
                            if (!$save_status) {
                                throw new \Exception("更新标放款状态 is_has_loans 失败");
                            }

                            $db->commit();
                        }else{
                            throw new \Exception("更新标状态 dealStatus 失败");
                        }
                    }catch(\Exception $e){
                        $db->rollback();
                        Logger::error("BatchJobsMakeLoans | run | Fail ".$deal['id']." error:".$e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * 是否有未完成的批作业
     */
    private function GetData($startTime, $endTime)
    {
        $sql = "SELECT * FROM `firstp2p_batch_job`  WHERE job_status=1 AND job_type=3 AND job_interval_start <=$endTime AND job_interval_end >=$startTime";
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        $result = array();
        foreach ($rows as $row) {
            $runTime = date('Y-m-d') . " " . $row['job_run_time'];
            $runTime = strtotime($runTime);
            if ($runTime >= $startTime && $runTime <= $endTime) {
                $result['deal_types'][] = $row['deal_type'];
            }
        }
        $result['records'] = $rows;
        return $result;
    }

    /**
     * 是否有未完成的批作业
     */
    private function CheckDeal($dealId)
    {
        $dealModel = new DealModel();
        $dealService = new DealService;
        $dealInfo = $dealModel->find($dealId,"*",true);
        try
        {
            $dealService->isOKForMakingLoans($dealInfo);
        } catch (\Exception $e) {
            Logger::error("BatchJobsMakeLoans | run | checkFail ".$e->getMessage());
            return false;
        }

        return true;
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 0);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new BatchJobMakeLoans();
$obj->run();