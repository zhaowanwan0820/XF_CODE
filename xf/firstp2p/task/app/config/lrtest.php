<?php
if (!defined('TASK_APP_NAME')) {
    exit('要用task系統, 必須在被用系統的義一個TASK_APP_NAME'."\n");
}

$settings = array(
    'application' => array(
        "name"         => "task",
        "namespace"    => "NCFGroup\\Task\\",
        "mode"         => "Cli",
        "metaDataDir"  => __DIR__.'/../../cache/metadata/',

    ),
    "taskDb" => array(
        'adapter' => 'Mysql',
        'host' => '10.20.69.206',
        'username' => 'root',
        'password' => '123456',
        'dbname' => 'taskdb',
        'port' => 3306,
    ),
    //切勿拷贝task的哨兵配置。
    'taskSentinels' => array(
        array(
            //'host' => '10.20.9.157',
            'host' => '10.20.69.205',
            'port' => '26479',
            'timeOutSec' => 1,
            'database' => 1,
            'sign' => 'daJE2fa23',
        ),
        array(
            //'host' => '10.20.9.157',
            'host' => '10.20.69.205',
            'port' => '26579',
            'timeOutSec' => 1,
            'database' => 1,
            'sign' => 'daJE2fa23',
        ),
        array(
            //'host' => '10.20.9.157',
            'host' => '10.20.69.205',
            'port' => '26679',
            'timeOutSec' => 1,
            'database' => 1,
            'sign' => 'daJE2fa23',
        ),
    ),
    'taskRedis' => array(
        'host' => '10.20.69.204',
        'port' => 6379,
        'timeout' => 1,
    ),

//    'redis' => array(
//        'host' => '10.20.69.204',
//        'port' => 6379,
//        'timeout' => 1,
//    ),

    'taskGearman' => array (
        'serverInfos' => array(
            array('ip' => '127.0.0.1', 'port' => 4730)
        ),
        'alertMails' => array(
            'wanghonglei@ucfgroup.com',
            //'jingxu@ucfgroup.com',
            //'qicheng@ucfgroup.com',
            //'wangjiansong@ucfgroup.com',
            //'guweigang@ucfgroup.com',
        ),
        'appName' => TASK_APP_NAME,
        'taskErrorPath' => '/tmp/task_error.log',
        'warningWorkerCnt' => 1,
    ),

    'taskLogger' => array(
        'file' => array(
            'path' => '/tmp/backend_task_'.date('Ymd').'.log',
        ),
    ),
    'logger' => array(
        'file' => array(
            'path' => '/tmp/backend_task_'.date('Ymd').'.log',
        ),
    ),
);

return $settings;
