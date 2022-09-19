<?php

require_once dirname(__FILE__).'/../../../app/init.php';
use libs\utils\Logger;
use core\service\CouponLogService;
use core\dao\CouponLogModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');

class CouponLogPay
{
    public function __construct($type)
    {
        $this->coupon_log_dao = CouponLogModel::getInstance($type);
        $this->coupon_log_service = new CouponLogService($type);
    }

    public function run($loadIds)
    {
        $logInfo = array(__CLASS__, __METHOD__);
        foreach ($loadIds as $loadId) {
            $couponLog = $this->coupon_log_dao->findByDealLoadId($loadId);
            if (!empty($couponLog)) {
                try {
                    $res = $this->coupon_log_service->pay($couponLog['id']);
                    if (empty($res)) {
                        throw new \Exception('结算失败', 1);
                    }
                } catch (\Exception $e) {
                    Logger::error(implode(' | ', array_merge($logInfo, array($loadId, $e->getMessage()))));
                }
            }
        }
    }
}

$opts = getopt('t:d:');
$type = isset($opts['t']) && trim($opts['t']) ? trim($opts['t']) : CouponLogService::MODULE_TYPE_P2P;
$loadIds = isset($opts['d']) && trim($opts['d']) ? trim($opts['d']) : '';

if (!empty($loadIds)) {
    $loadIds = explode(',', $loadIds);
    $couponLogPay = new CouponLogPay($type);
    $couponLogPay->run($loadIds);
}

exit('优惠码结算完毕');
