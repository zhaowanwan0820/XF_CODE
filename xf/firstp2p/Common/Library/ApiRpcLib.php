<?php

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\SignatureLib;
use NCFGroup\Common\Library\AesLib;

class ApiRpcLib {
    protected $config = null;

        /**
     * 特别的头信息
     */
    public $specialHeaders = [
        'Content-Type: application/json'
    ];

    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * 拼接参数
     */
    public function generateParams(array $params, $key) {
        // 需要传timestamp参数，防止一直重放
        if (empty($params['timestamp'])) {
            $params['timestamp'] = time();
        }

        $signData = array();
        foreach ($params as $keyName=>$value) {
            // 为空的值不参与签名
            if (empty($value)) {
                continue;
            }

            // 对于array的参数，保证值为string类型
            if (is_array($value)) {
                $signData[$keyName] = $this->encode($value);
            } else {
                $signData[$keyName] = $value;
            }
        }

        $params['sign'] = SignatureLib::generate($signData, $this->config->signatureKey);
        return $this->encode($params);
    }

    /**
     * 验证签名
     */
    public function verify(array $params) {
        // 检查时间戳参数，同时时间差值保证在300s以内
        if (empty($params['timestamp']) || abs($params['timestamp'] - time()) > 300) {
            return false;
        }

        $signData = array();
        foreach ($params as $keyName=>$value) {
            // 为空的值不参与签名
            if (empty($value)) {
                continue;
            }

            // 对于array的参数，保证值为string类型
            if (is_array($value)) {
                $signData[$keyName] = $this->encode($value);
            } else {
                $signData[$keyName] = $value;
            }
        }

        return SignatureLib::verify($signData, $this->config->signatureKey, 'sign');
    }

    /**
     * 加密数据
     */
    public function encode($data) {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 解密数据
     */
    public function decode($data) {
        return json_decode($data, true);
    }
}
