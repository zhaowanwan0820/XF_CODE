<?php
/**
 * 定时任务处理脚本
 */
require_once dirname(__FILE__).'/../app/init.php';
\FP::import("libs.utils.logger");
use core\dao\JobsModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');

class Jobs {
    public function run($id) {
        $jobs_model = new JobsModel();
        $job = $jobs_model->find($id);
            if ($job && $job->function) {
                $job->startJob();
                logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $job->id, "start")));
                $result = $job->runJob();
                $arr_log = array(__CLASS__, __FUNCTION__, $job->id, $job->function, $job->params);
                if ($result) {
                    $arr_log[] = "succ";
                    logger::info(implode(" | ", $arr_log));
                    $job->finishJob();
                } else {
                    $arr_log[] = "fail";
                    logger::info(implode(" | ", $arr_log));
                    $job->finishJob(false);
                }
            }
    }

}

$id = intval($argv[1]);
$obj = new Jobs();
$obj->run($id);
