<?php
define('APP', 'web');

try
{
    require dirname(__FILE__).'/http2https.php';
    require dirname(__FILE__).'/../app/init.php';

    //XSS检测
    $parttern = array('"', "'", '%27', '%3E', '%3C', '>', '<');
    if (strlen(str_replace($parttern, '', $_SERVER['QUERY_STRING'])) !== strlen($_SERVER['QUERY_STRING'])) {
        \libs\utils\Logger::error("XSSDetected. host:{$_SERVER['HTTP_HOST']}, query:{$_SERVER['QUERY_STRING']}");
        app_redirect('/');
    }

    //导入rpc配置
    \libs\utils\PhalconRPCInject::init();
    SiteApp::init()->run();
}
catch (\Exception $e)
{
    require_once dirname(__FILE__).'/../libs/utils/Logger.php';

    \libs\utils\Logger::error('InitException. message:'.$e->getMessage().', file:'.$e->getFile().', line:'.$e->getLine());
    $logId = \libs\utils\Logger::getLogId();
    // 展示错误页面
    require dirname(__FILE__).'/../web/views/exception.php';
}
