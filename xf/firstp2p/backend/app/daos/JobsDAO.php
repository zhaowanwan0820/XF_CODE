<?php
namespace NCFGroup\Ptp\daos;


use NCFGroup\Ptp\models\Firstp2pJobs;

class JobsDAO
{
     /**
     * @添加jobs
     * @param unknown $function
     * @param unknown $param
     * @param unknown $priority
     * @param string $start_time
     * @param number $retry_cnt
     */
    public static function addJobs($function,$param,$priority,$start_time = false,$retry_cnt = 3) {
        $jobsObj = new Firstp2pJobs();
        $jobsObj->function = $function;
        $jobsObj->params = empty($param) ? "" : addslashes(json_encode($param));
        $jobsObj->createTime = get_gmtime();
        $jobsObj->startTime = $start_time;
        $jobsObj->finishTime = 0;
        $jobsObj->retryCnt = $retry_cnt;
        $jobsObj->priority = $priority;
        $jobsObj->errMsg = '';
        $jobsObj->save();
    }

}
