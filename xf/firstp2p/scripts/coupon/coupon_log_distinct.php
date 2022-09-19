<?php

require_once dirname(__FILE__).'/../../app/init.php';
use libs\utils\Logger;
use core\service\CouponLogService;

set_time_limit(0);

class coupon_log_distinct
{
    public function run($type, $size)
    {
        $couponLogService = new CouponLogService($type);
        $maxId = $couponLogService->getMaxId();
        $list = $couponLogService->getDuplicateCouponLog($maxId - $size);

        if (!empty($list)) {
            foreach ($list as $value) {
                $condition = " id != {$value['id']} and deal_load_id={$value['deal_load_id']} and pay_status != 2 ";
                $rows = $couponLogService->findAllByCondition($condition);
                $ret = $couponLogService->deleteByCondition($condition);
                Logger::error(__CLASS__.' | '.__FUNCTION__.' | '.'firstp2p_coupon_log_'.$type.'出现重复数据,处理'.($ret ? '成功' : '失败').' | '.json_encode($rows));
                \libs\utils\Alarm::push('coupon_log', 'firstp2p_coupon_log_'.$type.'出现重复数据,处理'.($ret ? '成功' : '失败'), $rows);
            }
        }
    }
}

$type = isset($argv[1]) ? trim($argv[1]) : 'p2p';
$size = isset($argv[2]) ? intval($argv[2]) : '5000';
$coupon_log_distinct = new coupon_log_distinct();
$coupon_log_distinct->run($type, $size);
exit;
