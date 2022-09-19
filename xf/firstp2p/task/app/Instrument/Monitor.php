<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/12
 * Time: 16:59
 */

namespace NCFGroup\Task\Instrument;


class Monitor {
    /**
     * 累计量上报
     */
    public static function add($key, $count = 1)
    {
        try {
            $redis = getDI()->get('taskMonitor');
            if (!$redis) {
                getDI()->get('taskLogger')->info("MonitorAddFailed. key:{$key}, redis is a null object.");
                return false;
            }
            $now = intval(time() / 60) * 60;
            return $redis->HINCRBY("H_MONITOR_{$key}", $now, $count);
        } catch(\Exception $e) {
            getDI()->get('taskLogger')->info("Monitor error, err=" . $e->getMessage());
            return false;
        }
    }
}