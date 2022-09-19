<?php

namespace scripts;

require_once(dirname(__FILE__) . '/../../app/init.php');
$turn_on_coupon_pay = app_conf('COUPON_PAY_DISABLE');
if ($turn_on_coupon_pay == 1){
    echo 'system deny settlement';
    exit;
}
set_time_limit(0);
ini_set('memory_limit', '1024M');

$coupon_log_service = new \core\service\CouponJobsService();
$coupon_log_service->addTaskForAutoPayGold();
