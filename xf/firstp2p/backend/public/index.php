<?php
$startTime = time();
define('BACKEND_SERVICE_ENABLE', 1);

require(dirname(__DIR__) . '/../app/init.php');
require(dirname(__DIR__) . '/../Common/Phalcon/Bootstrap.php');

//导入rpc配置
\libs\utils\PhalconRPCInject::init();

$GLOBALS['phalcon_bootstrap'] = new NCFGroup\Common\Phalcon\Bootstrap(dirname(__DIR__), 'ncfwx');
$GLOBALS['phalcon_bootstrap']->exec();
$action = $_SERVER['REQUEST_URI'];
if ($action == '/api/candyActivity/activityCreateByToken') {
    $req = file_get_contents('php://input');
    $req = json_decode($req, true);
    $reqTime = $req['reqTime'];
    $token = $req['token'];
    $cost = time() - $reqTime;
    if ($cost >= 2) {
        \libs\utils\Logger::info(implode('|', ['BONUS_CANDY', date('Y-m-d H:i:s', $reqTime), date('Y-m-d H:i:s', $startTime), $token]));
    }
}
