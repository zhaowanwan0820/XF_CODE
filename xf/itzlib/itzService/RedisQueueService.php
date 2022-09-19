<?php

/**
 * Class RedisQueueService
 *
 * RedisQueue 类，提供最基本的入队出队操作。
 *
 *
 */

class RedisQueueService extends RedisService
{
    public function __construct($rcache = null)
    {
        parent::__construct();
        $this->rcache = $rcache ? $rcache : Yii::app()->rcache3;
    }

    /**
     * 入队
     * @param string $key
     * @param mixed $value
     * @param bool $positive
     * @return int|bool
     */
    public function enQueue($key, $value, $positive = true)
    {
        $value = json_encode($value);
        return $positive ? $this->lPush($key, $value) : $this->rPush($key, $value);
    }

    /**
     * 出队
     * @param string $key
     * @param bool $positive
     * @return mixed
     */
    public function deQueue($key, $positive = true)
    {
        $value =  $positive ? $this->rPop($key) : $this->lPop($key);
        return json_decode($value, true);
    }
}
