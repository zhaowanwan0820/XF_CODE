<?php
/**
 * 监控服务
 */
namespace libs\utils;

class Monitor
{
    /**
     * 业务类型_券类型_消息描述
     *
     * 优惠券签名验证失败
     */
    const O2O_DISCOUNT_SIGN_FAILD   = 'O2O_DISCOUNT_SIGN_FAILD';

    public function addMonitor($key, $count = 1) {
        return self::add($key, $count);
    }

    /**
     * 累计量上报
     */
    public static function add($key, $count = 1)
    {
        try {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if (!$redis) {
                throw new \Exception("getRedisInstance failed");
            }

            $now = intval(time() / 60) * 60;
            return $redis->HINCRBY('H_MONITOR_'.$key, $now, $count);
        } catch (\Exception $e) {
            \libs\utils\Logger::error("MonitorAddFailed. key:{$key}. message:".$e->getMessage());
        }
    }
}
