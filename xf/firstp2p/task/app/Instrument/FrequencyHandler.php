<?php
namespace NCFGroup\Task\Instrument;

class FrequencyHandler
{
    private $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public function canDo($key, $ttlSec)
    {
        return $this->redis->set('task_'.md5($key), 1, array('nx', 'ex' => $ttlSec));
    }

    /**
     * @param $key  string 关键字
     * @param $limit int 频率的限制值
     * @param int $period int 计时周期，单位s
     * @return bool  判断在$period 秒内，$key出现的次数是否超过$limit次，如果超过了，返回true，如果没有超过则返回false。
     */
    public function isBeyondFrequency($key, $limit, $period = 60) {
        if($limit <= 0) {
            return false;
        }

        $nowTime = microtime(true);

        $count = $this->redis->llen($key);
        if($limit > $count) {
            $this->redis->rpush($key, $nowTime);
            $this->redis->expire($key, $period);
            return false;
        }
        $firstTime = $this->redis->lindex($key, 0);
        $diffTime = round(($nowTime - $firstTime), 2);

        $this->redis->lpop($key);
        $this->redis->rpush($key, $nowTime);
        $this->redis->expire($key, $period);

        if($diffTime > $period) {//超过了计时周期，所以还是没有超过频率限制
            return false;
        }
        return true;
    }
}
