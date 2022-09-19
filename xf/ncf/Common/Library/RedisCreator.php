<?php
namespace NCFGroup\Common\Library;

/**
 * RedisCreator redis 工厂
 * redis扩展 接口文档 见https://github.com/nicolasff/phpredis.
 */
class RedisCreator
{
    public static function getRedis($redisSentinelConfigArr,$timeout = 1, $pconnect = false)
    {/*{{{*/
        static $ins = array();

        $key = md5(serialize($redisSentinelConfigArr));
        if (isset($ins[$key])) {
            return $ins[$key];
        }

        $ins[$key] = self::createAndConnectRedis($redisSentinelConfigArr,$timeout, $pconnect);

        return $ins[$key];
    }/*}}}*/

    public static function createAndConnectRedis($redisSentinelConfigArr, $timeout = 1, $pconnect = false)
    {/*{{{*/
        $masterInfo = self::getMasterInfo($redisSentinelConfigArr);
        $redis = new \Redis();
        if (!$pconnect) {
            $redis->connect($masterInfo['ip'], $masterInfo['port'], $timeout);
        } else {
            $redis->pconnect($masterInfo['ip'], $masterInfo['port'], $timeout);
        }

        return $redis;
    }/*}}}*/

    private static function getMasterInfo($redisSentinelConfigArr)
    {/*{{{*/
        $redis = new \Redis();
        foreach ($redisSentinelConfigArr as $redisSentinel) {
            $timeOutSec = isset($redisSentinel['timeOutSec']) ? $redisSentinel['timeOutSec'] : 1;
            if ($redis->connect($redisSentinel['host'], $redisSentinel['port'], $timeOutSec)) {
                $info = $redis->info('Sentinel');
                if (preg_match('#address=(?<ip>[\d,.]+):(?<port>\d+),#', $info['master0'], $matches)) {
                    return array('ip' => $matches['ip'], 'port' => $matches['port']);
                }
            }
        }

        throw new \Exception('redis sentinel 全挂了');
    }/*}}}*/
}
