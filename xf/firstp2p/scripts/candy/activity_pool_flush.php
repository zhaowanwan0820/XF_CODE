<?php

ini_set('display_errors', 'On'); error_reporting(E_ALL);
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\candy\CandyActivityService;
use core\service\candy\CandyBucService;
use libs\utils\Logger;

$service = new CandyActivityService();
$total = $service->getAllActivityTotalTodayToCache();
Logger::info("activity_pool_flush. total:{$total}");
echo $total."\n";

