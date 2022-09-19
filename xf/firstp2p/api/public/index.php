<?php
define('APP', 'api');


use libs\web\App;
try {

    require(dirname(__FILE__).'/../../app/init.php');
    $config = require(dirname(__FILE__) . '/../../conf/components.conf.php');
    // 导入rpc配置
    \libs\utils\PhalconRPCInject::init();
    // 种一个api的cookie，解决现在H5传site_id的问题
    if (!isset($_COOKIE['APP_SITE_ID'])) {
        $siteId = \libs\utils\Site::getId();
        setcookie('APP_SITE_ID', $siteId, 3600, '/', '', true, true);
    }
    // 启动api
    App::init($config)->run();
}
catch(\Exception $e) {
    require_once dirname(__FILE__).'/../../libs/utils/Logger.php';
    \libs\utils\Logger::error('InitException. message:'.$e->getMessage().', file:'.$e->getFile().', line:'.$e->getLine());
    // todo 返回api通用错误提示
}
