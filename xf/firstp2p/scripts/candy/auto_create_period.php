<?php
/**
 * Created by PhpStorm.
 * 信宝夺宝-商品自动上新
 * User: wangpeipei
 * Date: 2018/10/30
 * Time: 20:03
 */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\candy\CandySnatchService;

$service = new CandySnatchService();

try {
    $res =  $service->createPeriod();
} catch(\Exception $e) {
    Logger::info(" CREATE PERIOD EXCEPTION, " . $e->getMessage());
}