<?php
namespace libs\queue\TQueue;

use libs\queue\TQueue\TRedisQueue;
use libs\queue\TQueue\TMemcacheQueue;

/**
 * thunderQueue 驱动管理器
 **/
class DriverManager {
    const MAX_CONNECT_RETRY_TIMES = 3;
    static $instance = null;

    private function __construct() {
    }

    /**
     * 单例模式
     */
    static public function getManagerInstance() {
        if (empty(self::$instance)) {
            self::$instance = new DriverManager();
        }
        return self::$instance;
    }

    /**
     * @implements connect
     */
    static public function factory($thunderQueue) {
        if (empty($thunderQueue->server)) {
            throw new \Exception('ThunderQueueException - cannot accept empty server information!');
        }

        switch($thunderQueue->queueType) {
            case 'Redis':
                $link = self::getManagerInstance()->doRedisConnect($thunderQueue);
                return $link;
                break;
            case 'Memcache':
                // to be realized
                break;
            default:
                throw new \Exception('DriverManagerException - Unsupported cache driver type:' . $thunderQueue->queueType);
        }
    }


    /**
     * 创建redis thunderQueue 链接
     */
    public function doRedisConnect($thunderQueue) {
        $thunderQueue = new TRedisQueue($thunderQueue);
        return $thunderQueue;
    }
}
