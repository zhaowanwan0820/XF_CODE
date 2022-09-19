<?php
/**
 *
 * User: liuhaiyang (liuhaiyang@xxx.com)
 * Date: 2016/11/11
 */

namespace itzlib\sdk;

final class ServiceUtil
{
    public static function fetchNewestConfig()
    {
        try {
            $serviceDiscovery = new ServiceDiscovery(\ConfUtil::get("Zookeeper.address"));
        } catch (Exception $e) {
            \Yii::log("Service discovery failure! Message: {$e->getMessage()}", \CLogger::LEVEL_ERROR, __CLASS__);
            throw $e;
        }

        $serviceDiscovery->run();
        return true;
    }

    public static function getAddress($serviceId, $expire = 3600, $fromCache = true)
    {
        $file = '/tmp/service.json';
        // 当 文件不存在，不读取缓存，或者缓存时间过期 时重新获取新配置。
        if (
            !file_exists($file) ||
            $fromCache === false ||
            filemtime($file) + $expire < time()
        ) {
            self::fetchNewestConfig();
        }

        $content = json_decode(file_get_contents($file), true);

        $service = $content['service_list'][$serviceId];

        $protocol = $service['value']['protocol'];
        $providers = $service['providers'];

        if (empty($providers)) {
            return false;
        }

        $steps = [];
        $addresses = [];
        foreach ($providers as $url => $data) {
            $weight = $data['weight'];
            // 将权重按段位记录
            // 如可用 3 台 server ， 权重分别为 10, 20, 20
            // 则 steps 为 [10, 30, 50] ，
            // addresses 为 [server1, server2, server 3]
            $steps[] = end($steps) + $weight;
            $addresses[] = $url;
        }

        // 从 1 到权重最大值，随机选一个值，然后与 steps 中段位匹配
        $rand = mt_rand(1, end($steps));
        $choose = 0;
        foreach ($steps as $key => $value) {
            if ($rand <= $value) {
                $choose = $key;
                break;
            }
        }

        return $protocol . "://" . $addresses[$choose];
    }
}
