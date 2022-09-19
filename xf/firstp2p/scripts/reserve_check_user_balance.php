<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\service\UserReservationService;
use libs\utils\LOGGER;

require(APP_ROOT_PATH.'libs/utils/PhalconRPCInject.php');
\libs\utils\PhalconRpcInject::init();

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

$userReservationService = new UserReservationService();
$userReservationService->checkUserBalance();

//休息300秒
sleep(300);
