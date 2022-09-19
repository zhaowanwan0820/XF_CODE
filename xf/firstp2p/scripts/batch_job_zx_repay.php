<?php
require_once dirname(__FILE__).'/../app/init.php';

use core\service\DealProjectService;

use core\dao\JobsModel;
use core\dao\UserModel;
use core\dao\DealProjectModel;
use libs\utils\Logger;

class BatchJobZxRepay {

    public function run() {
        $begin15 = time()-900; // 取最近15分钟
        $end15 = time();

        $repayJobData = $this->HasNotFinishRepayBatchJob($begin15,$end15);
        if(!$repayJobData) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"没有需要执行的还款批作业startTime:{$begin15},endTime:{$end15}")));
            exit();
        }

        if($repayJobData['next_repay_time']) {
            $startTime = 0;
            $endTime  =  to_timespan(date('Y-m-d',$repayJobData['next_repay_time']));
        }else{
            $startTime = to_timespan(date("Y-m-d") . "00:00:00");
            $endTime  =  to_timespan(date("Y-m-d") . " 23:59:59");
        }

        $notRepays = $this->getNotRepay($startTime,$endTime); // 取得今日待还款列表
        if(empty($notRepays)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"待还款列表为空(不含扣负) startTime:{$startTime},endTime:{$endTime}")));
        }
    }


    /**
     * 开始还款处理逻辑
     * @param $repayId
     * @param $dealId
     * @return bool
     */
    public function doRepay($project_id){

        $admInfo = array(
            'adm_name' => 'system',
            'adm_id' => 0,
        );

        $project = DealProjectModel::instance()->find($project_id);
        if($project['business_status'] != DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying']){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"项目状态异常,不执行还款","project_id:".$project_id)));
            return true;
        }

        try{
            $GLOBALS['db']->startTrans();
            $repayType = 0;

            $param = array('project_id' => intval($project_id),'ignore_impose_money' => 0, 'admin' => $admInfo,'negative'=>0,'repayType'=>$repayType, 'submitUid' => 0, 'auditType' => 3);

            $job_model = new JobsModel();

            // 异步处理还款
            $function = '\core\service\DealRepayService::projectRepay';
            $job_model->priority = JobsModel::PRIORITY_PROJECT_REPAY;


            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("加入jobs失败");
            }


            $res = DealProjectModel::instance()->changeProjectStatus($project_id, DealProjectModel::$PROJECT_BUSINESS_STATUS['during_repay']);
            if(!$res) {
                throw new \Exception("改变项目还款状态失败");
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            echo $ex->getMessage();
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage(),"project_id:".$project_id)));
            return false;
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"成功插入jobs并更改了还款状态","project_id:".$project_id)));
        return true;
    }

    /**
     * 是否有未完成的批作业
     */
    private function HasNotFinishRepayBatchJob($startTime,$endTime) {
        $sql = "SELECT * FROM `firstp2p_batch_job`  WHERE job_status=1 AND job_type=4 AND job_interval_start <=$endTime AND job_interval_end >=$startTime";
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        $repayData = array();

        foreach($rows as $row) {
            $runTime = date('Y-m-d')." ".$row['job_run_time'];
            $runTime = strtotime($runTime);
            if($runTime >= $startTime && $runTime <=$endTime) {
                $repayData = $row;
                break;
            }
        }
        return $repayData;
    }

    /**
     * 取得今日未完成的还款
     */
    private function getNotRepay($startTime,$endTime) {
        $sql = "SELECT sum(t1.`repay_money`) as repay_money, t1.`user_id`,t2.project_id,p.fixed_value_date,p.business_status
                 FROM firstp2p_deal_repay t1
                 LEFT JOIN firstp2p_deal t2
                 ON t1.`deal_id` = t2.`id`
                 LEFT JOIN firstp2p_deal_project p
                 ON t2.project_id = p.id
	             WHERE t1.`repay_time` <= {$endTime}  AND t1.repay_time >={$startTime} AND t1.`status` = 0 AND t2.`is_delete` = 0 AND t2.`publish_wait` = 0 AND t2.deal_type = 3 AND t2.`deal_status` = 4 AND t2.`is_during_repay` = 0 AND p.`business_status` = 5 AND p.fixed_value_date > 0 group by t2.project_id desc;";

        $rows = $GLOBALS['db']->get_slave()->getAll($sql);

        $user = new UserModel();
        $deal_pro_service = new DealProjectService();

        $userMoneyArr = array();
        foreach($rows as $row) {
            if($deal_pro_service->isProjectYJ175($row['project_id'])){
                continue;
            }

            //过滤专享1.75标的
            if($deal_pro_service->isProjectEntrustZX($row['project_id'])){
                $userInfo = $user->find($row['user_id']);
                $userMoney = $userInfo['money'];

                // 进行余额预扣减
                $userMoneyArr[$userInfo['id']] = isset($userMoneyArr[$userInfo['id']]) ? bcsub($userMoneyArr[$userInfo['id']],$row['repay_money'],2) : bcsub($userMoney,$row['repay_money'],2);

                if(bccomp($userMoneyArr[$userInfo['id']],'0.00') < 0){//余额不足
                    $userMoneyArr[$userInfo['id']] = bcadd($userMoneyArr[$userInfo['id']],$row['repay_money'],2);
                    continue;
                }
            }

            $this->doRepay($row['project_id']);
        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new BatchJobZxRepay();
$obj->run();