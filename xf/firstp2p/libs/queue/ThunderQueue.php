<?php
namespace libs\queue;

use libs\base;
use libs\base\Component;
use libs\base\IComponent;
use libs\queue\TQueue\DriverManager;
use libs\queue\TQueue\IQueueAction;

/**
 * 基于redis的队列实现
 **/
class ThunderQueue extends Component implements IQueueAction, IComponent {

    const TSTRING = 1;
    const TARRAY = 2;

    const FULL_CAPACITY = -1;
    const HALF_CAPACITY = -2;

    /** @var $qtype 队列类型*/
    public $queueType = '';
    /** @var $servers 链接池 */
    public $servers = array();

    public $timeout = null;

    public $channel = '';

    public function init($obj = null) {
        if (empty($obj)) {
            $obj = $this;
        }
        $this->thunderQueue = DriverManager::factory($obj);
    }

    public function push($queueName, $queueData) {
        return $this->thunderQueue->push($queueName, $queueData);
    }

    public function pop($queueName, $limit = 1, $dataType = ThunderQueue::TSTRING) {
        return $this->thunderQueue->pop($queueName, $limit, $dataType);
    }

    public function getCapacity($queueName) {
        return $this->thunderQueue->getCapacity($queueName);
    }

    public function delete($queueName) {
        return $this->thunderQueue->delete($queueName);
    }
}
