<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/20
 * Time: 10:59
 */

namespace NCFGroup\Task\Instrument;


class SentinelRedis {

    const REDIS_HOST = 'host';
    const REDIS_PORT = 'port';

    private $sentinels = array();
    private $sentinelConnection = null;
    private $pConnect = false;
    private $timeout = 1;

    public function __construct($sentinelConfig, $timeOut = 1, $pConnect = false) {
        $this->setSentinels($sentinelConfig);
        $this->timeout = $timeOut;
        $this->pConnect = $pConnect;
    }

    public function setSentinel(array $sentinel)
    {
        if(!empty($sentinel[self::REDIS_HOST]) && !empty($sentinel[self::REDIS_PORT])) {
            array_push($this->sentinels,
                array(
                    self::REDIS_HOST => $sentinel[self::REDIS_HOST],
                    self::REDIS_PORT => $sentinel[self::REDIS_PORT]
                )
            );
        } else {
            throw new \Exception("Sentinel Config is Wrong. SentinelConfig=" . json_encode($sentinel));
        }
    }

    public function setSentinels(array $sentinels) {
        foreach($sentinels as $sentinel) {
            $this->setSentinel($sentinel);
        }
    }

    public function getRedisInstance() {
        $sentinelRedis = $this->getSentinelConnection();
        if(is_null($sentinelRedis)) {
            return false;
        }
        $redisInfo = $sentinelRedis->info("sentinel");
        if (preg_match('#address=(?<ip>[\d,.]+):(?<port>\d+),#', $redisInfo['master0'], $matches)) {
            $redis = new \Redis();
            if($this->pConnect) {
                $result = $redis->pconnect($matches['ip'], $matches['port'], $this->timeout);
            } else {
                $result = $redis->connect($matches['ip'], $matches['port'], $this->timeout);
            }
            if(!$result) {
                return false;
            }
            return $redis;
        }
        return false;
    }

    public function getSentinels($idx = null, $key = '')
    {
        if(is_null($idx)) {
            return $this->sentinels;
        }

        if(isset($this->sentinels[$idx])) {
            $sNode = $this->sentinels[$idx];
            if(isset($sNode[$key])) {
                return $sNode[$key];
            } else {
                return $sNode;
            }
        }
        throw new \OutOfBoundsException();
    }

    public function getSentinelConnection() {
        if(empty($this->sentinels)) {
            throw new \Exception("There is no Sentinels.");
        }

        if($this->sentinelConnection === null) {
            $i = 0;
            $idx = null;
            do {
                if(++$i > 1) {
                    unset($this->sentinels[$idx]);
                }
                $idx = array_rand($this->sentinels);

                $this->sentinelConnection = new \Redis();
                $host = $this->getSentinels($idx, self::REDIS_HOST);
                $port = $this->getSentinels($idx, self::REDIS_PORT);
                $this->sentinelConnection->connect($host, $port, $this->timeout);
            } while($i < 3 && $this->sentinelConnection->ping() == false);
        }
        return $this->sentinelConnection;
    }
}