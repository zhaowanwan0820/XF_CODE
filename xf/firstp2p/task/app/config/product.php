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
        'host' => 'w-taskdb.dbs.wxlc.org',
        'username' => 'taskdb_pro',
        'password' => 'sa1ve1Pa2kIGNczi',
        'dbname' => 'taskdb',
        'charset' => 'utf8',
        'port' => 3308,
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
            'host' => 'st-redis2.wxlc.org',
            'port' => '26479',
            'timeOutSec' => 1,
            'database' => 1,
            'sign' => 'daJE2fa23',
        ),
        array(
            'host' => 'st-redis3.wxlc.org',
            'port' => '26479',
            'timeOutSec' => 1,
            'database' => 1,
            'sign' => 'daJE2fa23',
        ),
    ),

    'taskRedis' => array(
        'host' => 'se-redis1.wxlc.org',
        'port' => 6379,
        'timeout' => 3,
    ),

//    'redis' => array(
//        'host' => 'se-redis1.wxlc.org',
//        'port' => 6379,
//        'timeout' => 3,
//    ),

    'taskGearman' => array (
        'serverInfos' => array(
            array('ip' => 'jobserver1.wxlc.org', 'port' => 4730),
            array('ip' => 'jobserver2.wxlc.org', 'port' => 4730),
        ),
        'alertMails' => array(
            'liaoyebin@ucfgroup.com',
            'quanhengzhuang@ucfgroup.com',
        ),
        'appName' => TASK_APP_NAME,
        'taskErrorPath' => '/tmp/task_error.log',
        'warningWorkerCnt' => 264,
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
