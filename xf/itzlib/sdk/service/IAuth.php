<?php

/**
 * IAuth SDK 客户端
 * User: Devon
 * Date: 16/8/23
 * Time: 11:10
 */

namespace itzlib\sdk\service;

use itzlib\sdk\SdkClient;

class IAuth extends SdkClient
{
    /**
     * @var string APP Key
     */
    public $key = '1Ams9ey';
    /**
     * @var string APP Secret
     */
    public $secret = 'sJLo2w9W5RwV';
    /**
     * @var string iAuth 服务位于 zookeeper 树的名称
     */
    public $service = 'com.itouzi.iauth';
    /**
     * @var string
     */
    const VERSION = 'v1';

    public function login($username, $password)
    {
        $verb = 'POST';
        $path = 'api/v1/user/login';
        $params = [
            'username' => $username,
            'password' => $password
        ];

        return $this->curl($verb, $path, $params);
    }

    public function userCan($uid, $code)
    {
        $verb = 'GET';
        $path = 'api/v1/user/can';
        $params = [
            'uid' => $uid,
            'code' => $code
        ];

        return $this->curl($verb, $path, $params);
    }

    public function getUserAuthList($uid)
    {
        $verb = 'GET';
        $path = 'api/v1/user/authList';
        $params = ['uid' => $uid];

        return $this->curl($verb, $path, $params);
    }

    public function sendCode($uid, $operation = '后台操作', $identifier = '')
    {
        $identifier = $identifier ?: session_id();

        $verb = 'GET';
        $path = 'api/v1/dualFactor/send';
        $params = [
            'uid' => $uid,
            'identifier' => $identifier,
            'operation' => $operation
        ];

        return $this->curl($verb, $path, $params);
    }

    public function verifyCode($code, $identifier = '')
    {
        $identifier = $identifier ?: session_id();

        $verb = 'GET';
        $path = 'api/v1/dualFactor/verify';
        $params = [
            'code' => $code,
            'identifier' => $identifier,
        ];

        return $this->curl($verb, $path, $params);
    }



}
