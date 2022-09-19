<?php
/**
 * 补刀脚本，补普惠邀请码当前30分钟之前到60分钟之间的数据.
 */
require_once dirname(__FILE__).'/../../app/init.php';

use libs\utils\Logger;
use core\service\CouponService;
use core\service\CouponLogService;
use core\service\CouponDealService;
use core\service\third\ThirdDealLoadService;
use core\dao\third\ThirdDealLoadModel;
use core\dao\third\ThirdDealModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

class coupon_log_async
{
    public function run()
    {
        $couponLogService = new CouponLogService('third');
        $thirdDealLoadService = new ThirdDealLoadService();
        $thirdDealLoadDao = new ThirdDealLoadModel();
        $opts = getopt('s:d:c:e:');
        $time = time() - 86400;
        $startId = isset($opts['s']) && intval($opts['s']) ? intval($opts['s']) : $thirdDealLoadDao->getStartId($time - 1800);
        $endId = isset($opts['e']) && intval($opts['e']) ? intval($opts['e']) : $thirdDealLoadDao->getEndId($time);
        $dealLoadId = isset($opts['d']) && intval($opts['d']) ? intval($opts['d']) : 0;
        $do = isset($opts['c']) && intval($opts['c']) ? intval($opts['c']) : 0;
        $size = 100;

        if (0 != $dealLoadId) {
            $this->consume($dealLoadId, $do);
        } else {
            while ($startId <= $endId) {
                $loadIds = $thirdDealLoadService->getDealLoadIds($startId, $size);
                $startId += $size;
                if (empty($loadIds)) {
                    break;
                }

                $notInLoadIds = $couponLogService->getNotInCouponLogLoadIds($loadIds);
                if (!empty($notInLoadIds)) {
                    foreach ($notInLoadIds as $loadId) {
                        $this->consume($loadId, $do);
                    }
                }
            }
        }
    }

    private function consume($loadId, $do)
    {
        try {
            $dealLoad = ThirdDealLoadModel::Instance()->getDealLoadById($loadId);
            if (empty($dealLoad)) {
                throw new Exception("投资记录信息不存在[{$loadId}]");
            }

            $dealInfo = ThirdDealModel::Instance()->getInfoByDealIdAndClientId($dealLoad['deal_id'], $dealLoad['client_id']);
            if (empty($dealInfo)) {
                throw new Exception("标信息不存在[{$dealLoad['deal_id']}]");
            }

            $couponDealService = new CouponDealService('third');
            $couponDealInfo = $couponDealService->getCouponDealByDealId($dealInfo['id']);
            if (empty($couponDealInfo)) {
                throw new Exception("标的邀请码信息不存在[{$dealInfo['id']}]");
            }

            $coupon_fields = array();
            $coupon_fields['deal_id'] = $dealInfo['id'];
            $coupon_fields['money'] = $dealLoad['money'];
            $coupon_fields['site_id'] = 1;
            $coupon_fields['client_id'] = $dealLoad['client_id'];
            $couponService = new CouponService('third');
            if (0 != $do) {
                $res = $couponService->consume($loadId, '', $dealLoad['user_id'], $coupon_fields, CouponService::COUPON_SYNCHRONOUS);
                if (empty($res)) {
                    throw new Exception("消费邀请码失败[{$loadId}]");
                }
            }
        } catch (\Exception $e) {
            \libs\utils\Alarm::push('coupon_log_async', 'coupon_log_third_error:'.$e->getMessage(), json_encode($dealLoad));
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'data:'.json_encode($dealLoad), 'deal_load 数据进入coupon_log_third 处理失败')));
            return false;
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'data:'.json_encode($dealLoad), 'deal_load 数据进入coupon_log_third 处理成功')));
    }
}
$time = time();
$coupon_log_async = new coupon_log_async();
$coupon_log_async->run();
exit('第三方邀请码异步任务处理完毕,总共消耗'.(time() - $time).'秒');
