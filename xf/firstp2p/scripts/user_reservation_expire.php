<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\service\UserReservationService;
use libs\utils\LOGGER;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

$pageSize = 1000;
$processTime = time(); //执行时间

$userReservationService = new UserReservationService();
$userReservationService->expire($processTime, $pageSize);

//休息600秒
sleep(600);
