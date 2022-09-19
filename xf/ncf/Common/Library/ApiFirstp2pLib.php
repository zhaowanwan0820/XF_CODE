<?php

use NCFGroup\Common\Library\SignatureLib;
use NCFGroup\Common\Library\AesLib;

namespace NCFGroup\Common\Library;

class ApiFirstp2pLib
{

    private $config = null;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 拼接参数
     */
    public function generateParams(array $params)
    {
        $params['signature'] = SignatureLib::generate($params, $this->config->signatureKey);
        return $params;
    }

    /**
     * 验证签名
     */
    public function verify(array $params)
    {
        return SignatureLib::verify($params, $this->config->signatureKey, 'signature');
    }

    /**
     * 加密数据
     */
    public function encode($data)
    {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $result = AesLib::encode($data, base64_decode($this->config->aesKey));

        return $result;
    }

    /**
     * 解密数据
     */
    public function decode($data)
    {
        $result = AesLib::decode($data, base64_decode($this->config->aesKey));
        $resultArray = json_decode($result, true);

        return $resultArray;
    }

}
