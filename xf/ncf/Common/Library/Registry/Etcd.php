<?php

namespace NCFGroup\Common\Library\Registry;

use NCFGroup\Common\Library\Curl;
use NCFGroup\Common\Library\CommonLogger as Logger;

class Etcd
{

    // 认证接口
    const API_AUTH_AUTHENTICATE = '/v3beta/auth/authenticate';

    // 读取kv接口
    const API_KV_RANGE = '/v3beta/kv/range';

    private $hosts = array();

    private $token = '';

    public function __construct(array $hosts, $username, $password)
    {
        shuffle($hosts);
        $this->hosts = $hosts;
        $this->token = $this->authenticate($username, $password);
    }

    /**
     * 获取一个服务的信息(包括IP、端口等信息)
     */
    public function getServiceInfo($prefix)
    {
        $all = $this->getAllServiceInfo($prefix);
        $one = $all[array_rand($all)];

        Logger::info('get service info. info:'.json_encode($one));
        return $one;
    }

    private function getAllServiceInfo($prefix)
    {
        $params = array(
            'key' => base64_encode($prefix),
            'range_end' => base64_encode($this->getRangeEnd($prefix)),
        );

        $result = $this->requestWithRetry(self::API_KV_RANGE, $params, $this->token);
        if (empty($result['kvs'])) {
            throw new \Exception('get service info failed');
        }

        $info = array();
        foreach ($result['kvs'] as $value) {
            $info[] = json_decode(base64_decode($value['value']), true);
        }
        return $info;
    }

    private function getRangeEnd($prefix)
    {
        if (strlen($prefix) < 1) {
            throw new \Exception('prefix is empty');
        }

        $lastIndex = strlen($prefix) - 1;
        $prefix[$lastIndex] = chr(ord($prefix[$lastIndex]) + 1);
        return $prefix;
    }

    private function authenticate($username, $password)
    {
        $result = $this->requestWithRetry(self::API_AUTH_AUTHENTICATE, array(
            'name' => $username,
            'password' => $password,
        ));

        if (empty($result['token'])) {
            throw new \Exception('get etcd token failed');
        }
        return $result['token'];
    }

    private function requestWithRetry($uri, array $params, $token = '')
    {
        foreach ($this->hosts as $host) {
            $result = $this->request($host, $uri, json_encode($params), $token);
            if ($result !== false) {
                return $result;
            }
        }
    }

    private function request($host, $uri, $paramsJson, $token)
    {
        $curl = Curl::instance();
        $response = $curl->setOpt(CURLOPT_HTTPHEADER, array("Authorization : {$token}"))->post($host.$uri, $paramsJson);
        if (empty($response)) {
            Logger::info("request etcd failed. code:{$curl->resultInfo['code']}, cost:{$curl->resultInfo['cost']}, error:{$curl->resultInfo['error']}, host:{$host}, uri:{$uri}");
            return false;
        }

        Logger::info("request etcd success. cost:{$curl->resultInfo['cost']}, host:{$host}, uri:{$uri}");
        return json_decode($response, true);
    }

}
