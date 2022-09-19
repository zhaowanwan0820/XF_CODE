<?php
/**
 * 更新通知贷返点比例金额
 * @date 2016-03-30
 * @author wangzhen <wangzhen3@ucfgroup.com>
 */

namespace scripts;
use core\service\CouponLogService;

require_once(dirname(__FILE__) . '/../app/init.php');
$turn_on_coupon_pay = app_conf('COUPON_PAY_DISABLE');
if ($turn_on_coupon_pay == 1){
    echo 'system deny settlement';
    exit;
}
set_time_limit(0);
$type = 'p2p';

$couponJobsService = new \core\service\CouponJobsService();
$couponJobsService->addTaskForUpdateCompoundRebateRatioAmount($type);

