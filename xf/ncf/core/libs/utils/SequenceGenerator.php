<?php
/**
 * Created by PhpStorm.
 * User: dengyi
 * Date: 2015/8/28
 * Time: 17:49
 */

namespace libs\utils;
use Zookeeper;

/**
 * Class SequenceGenerator
 * 用于产生全局唯一的名称和序号。用Zookeeper来做分布式锁，redis分布式锁来说，当PHP-FPM异常退出后，序列号也不会产生错误，也不会产生死锁。
 * 使用前必须安装zookeeper和zookeeper的PHP扩展模块才能够使用。
 * @package libs\utils
 */
class SequenceGenerator {

    const SEQUENCE_DEFAULT_KEY = "sequence-generator";
    const INVALID_SEQUENCE_NUMBER = -1;

    public $acl = array(
        array(
            'perms' => Zookeeper::PERM_ALL,
            'scheme' => 'world',
            'id' => 'anyone',
        )
    );

    public $connectionTimeout = 5;
    public $sleepCycle = 0.1;

    public static $zookeeper;
    private static $_knownKeys = array();

    public function __construct($host = '127.0.0.1:2181', $renew = false) {
        if($renew && isset(self::$zookeeper)) {
            self::$zookeeper = null;
        }
        if(empty(self::$zookeeper)) {
            self::$zookeeper = new Zookeeper();
            self::$zookeeper->connect($host);
            $this->_waitForConnection();
        }
    }

    private function _waitForConnection() {
        $deadline = microtime(true) + $this->connectionTimeout;
        while(self::$zookeeper->getState() != Zookeeper::CONNECTED_STATE) {
            if($deadline <= microtime(true)) {
                throw new \RuntimeException("Zookeeper connection timed out!");
            }
            usleep($this->sleepCycle * 100000);
        };
    }

    /**
     * @param string $sequenceKey，相当于redis的key。
     * @return array(
     *                  'sequence_name' => 'xxxx',
     *                  'sequence_number'   =>  xx
     *                  )
     * 返回一个全局唯一的名称(sequence_name)和当前的序号sequence_number(0,1,2...)，
     * 可以用这个序号来做并发控制，比如说，允许5个人同时并发，则序号小于5的都可以并发，其他的都不并发。
     */
    public function getSequenceNumber($sequenceKey = SequenceGenerator::SEQUENCE_DEFAULT_KEY) {
        try {
            $sequencePath = "/" . ltrim($sequenceKey, '/');
            if(!self::$_knownKeys[$sequencePath]) {
                if(!self::$zookeeper->exists($sequencePath)) {
                    $parentZonde = self::$zookeeper->create($sequencePath, null, $this->acl);
                    if(!$parentZonde) {
                        throw new \Exception("create path {$sequencePath} failed");
                    }
                    self::$_knownKeys[$sequencePath] = true;
                }
            }
            $znodePath = self::$zookeeper->create($sequencePath . '/', null, $this->acl, Zookeeper::EPHEMERAL | Zookeeper::SEQUENCE);
            if(!$znodePath) {
                throw new \Exception("create ephemeral and sequence subpaht of {$znodePath} failed");
            }
            $result['sequence_name'] = $znodePath;
            $znodeName = str_replace($sequencePath . '/', '', $znodePath);
            $childNodes = self::$zookeeper->getChildren($sequencePath);
            sort($childNodes);
            $number = array_search($znodeName, $childNodes);
            $result['sequence_number'] = $number;
        } catch(\Exception $e) {
            $result['sequence_name'] = '';
            $result['sequence_number'] = self::INVALID_SEQUENCE_NUMBER;
        }
        return $result;
    }

    /**
     * @param $sequenceName
     * 手动删除全局唯一的名称（这个sequenceName 是通过getSequenceNumber获得的），相当于释放锁的操作。
     */
    public function deleteSequenceNumber($sequenceName) {
        return self::$zookeeper->delete($sequenceName);
    }
}
//示例
//$sequenceGenerator = new SequenceGenerator();
//$sequence1 = $sequenceGenerator->getSequenceNumber();
//$sequence2 = $sequenceGenerator->getSequenceNumber();
//$sequence3 = $sequenceGenerator->getSequenceNumber();
//$childNodes = SequenceGenerator::$zookeeper->getChildren('/' . SequenceGenerator::SEQUENCE_DEFAULT_KEY);
//var_dump($childNodes);
//$result = $sequenceGenerator->deleteSequenceNumber($sequence1['sequence_name']);
//var_dump($result);
//$childNodes = SequenceGenerator::$zookeeper->getChildren('/' . SequenceGenerator::SEQUENCE_DEFAULT_KEY);
//var_dump($childNodes);