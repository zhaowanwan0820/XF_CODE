<?php
/**
 * coupon_auto_pay.php
 *
 * 普通标优惠码自动结算程序
 * 0 6 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php coupon_auto_pay.php
 *
 * @date 2015-02-04
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace scripts;

require_once(dirname(__FILE__) . '/../app/init.php');
$turn_on_coupon_pay = app_conf('COUPON_PAY_DISABLE');
if ($turn_on_coupon_pay == 1){
    echo 'system deny settlement';
    exit;
}
set_time_limit(0);
ini_set('memory_limit', '1024M');

$coupon_log_service = new \core\service\CouponJobsService();
$coupon_log_service->addTaskForAutoPay();
