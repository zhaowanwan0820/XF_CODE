<?php

define('APP', 'openapi');
define('ROOT_PATH', realpath(dirname(__FILE__).'/../../../').DIRECTORY_SEPARATOR);
require(ROOT_PATH.'core/framework/init.php');

use libs\web\App;

try {
    $config = require(ROOT_PATH . 'config/components.conf.php');
    App::init($config)->run();
} catch (\Exception $e) {
    require_once ROOT_PATH.'core/libs/utils/Logger.php';
    \libs\utils\Logger::error('InitException. message:'.$e->getMessage().', file:'.$e->getFile().', line:'.$e->getLine());
}