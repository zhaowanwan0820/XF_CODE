<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/12/9
 * Time: 11:02
 */

namespace NCFGroup\Task\Instrument;


class BigIntegerIdentityGenerator extends AbstractIdGenerator {

    private $_redis;
    const REDIS_ID_GENERATOR = "/string/id_generator";
    //允许在65535个并发而不产生重复的id。
    const CONCURRENCY_NUM = 65535;
    public function __construct($redis) {
        $this->_redis = $redis;
    }

    public function generate() {
        $id = $this->_redis->incr(self::REDIS_ID_GENERATOR);
        $nowTime = date("YmdHis");
        return $nowTime . sprintf("%05d", $id % self::CONCURRENCY_NUM);
    }

}