<?php
/**
 * Id生成器客户端
 */
namespace NCFGroup\Common\Library;

class Idworker
{

    /**
     * 请求超时(s)
     */
    const REQUEST_TIMEOUT = 1;

    private static $instance = null;

    /**
     * 单例化
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() { }

    /**
     * 获取Id
     */
    public function getId()
    {
        $config = getDi()->getConfig()->idworker->serverlist->toArray();
        shuffle($config);

        foreach ($config as $item) {
            $url = "http://{$item}/getid";
            $response = $this->request($url);
            $result = json_decode($response, true);
            if (!empty($result['id'])) {
                return intval($result['id']);
            }
        }

        throw new \Exception('Idworker getId failed');
    }

    /**
     * 解析Id
     */
    public function parseId($id)
    {
        $config = getDI()->getConfig()->idworker->serverlist->toArray();
        shuffle($config);

        foreach ($config as $item) {
            $url = "http://{$item}/parse/{$id}";
            $response = $this->request($url);
            $result = json_decode($response, true);
            if (!empty($result['timestamp'])) {
                return $result;
            }
        }

        throw new \Exception('Idworker parseId failed');
    }

    private function request($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $requestStart = microtime(true);
        $result = curl_exec($ch);
        $cost = round(microtime(true) - $requestStart, 3);

        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $result;
    }

}
