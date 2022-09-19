<?php
namespace libs\queue\TQueue;

use libs\queue\TQueue\IQueueAction;
use libs\queue\TQueue\DriverManager;
use libs\queue\ThunderQueue;

/**
 * 基于redis的队列实现
 **/
class TRedisQueue implements IQueueAction {
    private $link = null;


    public function __construct($thunderQueue){
        $this->link = new \Redis();
        $this->link->connect($thunderQueue->server['hostname'], $thunderQueue->server['port']);

        if (!$this->link->ping()) {
            throw new \Exception('TRedisQueue - Cannot connect to servers.');
        }

        $this->link->select($thunderQueue->server['database']);
    }

    public function push($queueName, $queueData) {
        $dataType = gettype($queueData);
        if(!in_array($dataType, array('string', 'integer', 'array'))) {
            throw new \Exception('ThunderQueue - Unsupported data format, except string, numeric, array');
        }
        if ($dataType === 'array') {
            $queueData = json_encode($queueData);
        }
        return $this->link->lpush($queueName, $queueData);
    }

    public function pop($queueName, $limit = 1, $dataType = ThunderQueue::TSTRING) {
        $i = 0;
        if ($limit === ThunderQueue::FULL_CAPACITY) {
            $limit = $this->getCapacity($queueName);
        }
        $curData = array();
        do {
            $data = $this->link->rpop($queueName);
            //if (empty($data)) {
            //    break;
            //}
            //switch ($dataType) {
            //    case ThunderQueue::TARRAY:
            //        if (gettype($data) != 'array') {
            //            $data = array($data);
            //        }
            //        else {
            //            $data = json_decode($data, true);
            //            if (json_last_error() != 0) {
            //                $data = array($data);
            //            }
            //        }
            //        break;
            //    case ThunderQueue::TSTRING:
            //        $data = json_decode($data, true);
            //    default:
            //}
            $curData[] = $data;
        } while (!$curData || ++ $i< $limit);
        if ($limit === 1 ) {
            return array_pop($curData);
        }
        return $curData;
    }

    public function getCapacity($queueName) {
        return $this->link->llen($queueName);
    }

    public function delete($queueName) {
        return $this->link->del($queueName);
    }
}
