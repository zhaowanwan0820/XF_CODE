<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\service\UserReservationService;
use libs\utils\LOGGER;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

if (count($argv) != 2) {
    exit('参数错误');
}

$dealType = (int) $argv[1];

$userReservationService = new UserReservationService();
$userReservationService->collect($dealType);

sleep(60);
