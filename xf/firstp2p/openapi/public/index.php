<?php

define('APP', 'openapi');

require(dirname(__FILE__) . '/../../app/init.php');

use libs\web\App;
use openapi\lib\Open;


try {
    $config = require(dirname(__FILE__) . '/../../conf/components.conf.php');
    // 导入rpc配置
    \libs\utils\PhalconRPCInject::init();
    // 启动openapi
    Open::init($config)->run();
}
catch (\Exception $e) {
    require_once dirname(__FILE__).'/../../libs/utils/Logger.php';
    \libs\utils\Logger::error('InitException. message:'.$e->getMessage().', file:'.$e->getFile().', line:'.$e->getLine());
    // todo 返回openapi通用错误提示
}

