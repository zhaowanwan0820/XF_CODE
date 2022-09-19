<?php

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\CommonLogger;

/**
 * 消息总线客户端
 */
class Msgbus
{

    /**
     * 请求超时(s)
     */
    const REQUEST_TIMEOUT = 2;

    const ZK_MSGROUTER_PATH = '/msgrouter/servers';

    private $zkHosts = array();

    private $zkInstance = null;

    private static $instance = null;

    /**
     * 获取一个单例
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->zkHosts = getDi()->getConfig()->msgbus->zookeepers->toArray();
    }

    /**
     * 生产消息
     */
    public function produce($topic, $message)
    {
        if (!is_string($message)) {
            $message = json_encode($message);
        }

        $result = $this->request('/producer/produce', array(
            'topic' => $topic,
            'message' => $message,
        ));

        if ($result['code'] != 0) {
            throw new \Exception('msgbus produce failed: '.$result['message']);
        }

        return $result['data'];
    }

    /**
     * 获取一个zk连接实例
     * todo 轮循获取一个可用结点
     */
    public function getZkInstance()
    {
        if ($this->zkInstance === null) {
            $zkHosts = $this->zkHosts;
            shuffle($zkHosts);

            $this->zkInstance = new \ZooKeeper(current($zkHosts));
        }

        return $this->zkInstance;
    }

    /**
     * 从zk获取任意一个msgbus server地址
     */
    private function getServerHost()
    {
        $keys = $this->getZkInstance()->getChildren(self::ZK_MSGROUTER_PATH);
        if (empty($keys)) {
            throw new \Exception('msgbus get zk server keys failed');
        }

        $key = $keys[array_rand($keys)];
        $result = $this->getZkInstance()->get(self::ZK_MSGROUTER_PATH.'/'.$key);
        $server = json_decode($result, true);

        if (empty($server['Host'])) {
            throw new \Exception('msgbus get zk server host failed');
        }

        return $server['Host'].':'.$server['Port'];
    }

    /**
     * 请求msgbus server端
     */
    public function request($action, array $params = array(), $host = '')
    {
        if ($host === '') {
            $host = $this->getServerHost();
        }

        $url = "http://{$host}{$action}";
        $params = http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $start = microtime(true);
        $result =  curl_exec($ch);
        $cost = round(microtime(true) - $start, 4);
        curl_close($ch);

        CommonLogger::info("msgbus request. url:{$url}, cost:{$cost}, params:{$params}, result:{$result}");

        $result = json_decode($result, true);
        if (empty($result)) {
            throw new \Exception("request {$url} failed");
        }

        return $result;
    }

}
