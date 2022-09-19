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
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
        'dbname' => 'taskdb',
        'port' => 3306,
    ),

    'taskRedis' => array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 1,
    ),
    
    //切勿拷贝task的哨兵配置。
    'taskSentinels' => array(
        array(
            'host' => 'st-redis1.wxlc.org',
            'port' => '26479',
            'timeOutSec' => 1,
            'database' => 1,
            'sign' => 'daJE2fa23',
        ),
        array(
            'host' => 'st-redis1.wxlc.org',
            'port' => '26579',
            'timeOutSec' => 1,
            'database' => 1,
            'sign' => 'daJE2fa23',
        ),
        array(
            'host' => 'st-redis1.wxlc.org',
            'port' => '26679',
            'timeOutSec' => 1,
            'database' => 1,
            'sign' => 'daJE2fa23',
        ),
    ),
//    'redis' => array(
//        'host' => '127.0.0.1',
//        'port' => 6379,
//        'timeout' => 1,
//    ),

    'taskGearman' => array (
        'serverInfos' => array(
            array('ip' => '127.0.0.1', 'port' => 4730)
        ),
        'alertMails' => array(
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
            'path' => '/apps/product/nginx/htdocs/task/logs/task_'.date('Ymd').'.log',
            'savePath' => '/apps/product/nginx/htdocs/task/logs/task_save_'.date('Ymd').'.log',
            'shutDownPath' => '/apps/product/nginx/htdocs/task/logs/task_shutdown_'.date('Ymd').'.log',
            'exceptionPath' => '/apps/product/nginx/htdocs/task/logs/task_exception_'.date('Ymd').'.log',
        ),
    ),
    'logger' => array(
        'file' => array(
            'path' => '/apps/product/nginx/htdocs/task/logs/task_'.date('Ymd').'.log',
            'savePath' => '/apps/product/nginx/htdocs/task/logs/task_save_'.date('Ymd').'.log',
            'shutDownPath' => '/apps/product/nginx/htdocs/task/logs/task_shutdown_'.date('Ymd').'.log',
            'exceptionPath' => '/apps/product/nginx/htdocs/task/logs/task_exception_'.date('Ymd').'.log',
        ),
    ),
);

return $settings;
