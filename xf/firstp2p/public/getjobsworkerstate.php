<?php
/**
 * GetJobsWorkerstate
 * @return 环境db及JobsWorker情况
 * @date 2016-01-28
 * @author 樊靖雯 <fanjignwen@ucfgroup.com>
 */

//// 此header为了解决跨域问题 (当客户端用js调用时)
//header("Access-Control-Allow-Origin:*");

require_once dirname(__FILE__).'/../app/init.php';
use core\service\GetJobsWorkerStateService;

$env = app_conf("ENV_FLAG");
if (in_array($env, array('dev', 'test'))) {
    $service = new GetJobsWorkerStateService();

    // 获取数据库配置
    $dbConf = $service->getDbConfig();
    //print_r($dbConf);
    //var_dump($dbConf);
    // 获取jobs_worker.php执行状况监测

    $strPro = $service->getJobsWorkerStatus();
    //print_r($strPro);

    // 输出json格式的数据
    $arr = array(
            "dbip" => $dbConf['DB_HOST'],
            "jobsworker" => $strPro,
    );
    $json = json_encode($arr);
    echo $json;
} else {
    exit("File not found");
}
?>
