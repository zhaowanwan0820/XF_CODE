<?php
namespace scripts;

// 按单个deal id结算，供测试用

require_once(dirname(__FILE__) . '/../../app/init.php');
$turn_on_coupon_pay = app_conf('COUPON_PAY_DISABLE');
if ($turn_on_coupon_pay == 1){
    echo 'system deny settlement';
    exit;
}
set_time_limit(0);
ini_set('memory_limit', '1024M');

$deal_id = $argv[1];
$type = $argv[2];

if (empty($deal_id) || empty($type)) {
    exit ('error deal_id or type');
}

$coupon_log_service = new \core\service\CouponLogService();
$rs = $coupon_log_service->payForDeal($deal_id, $type);
echo "deal_id[$deal_id] type[$type] done:" . json_encode($rs);
return true;
