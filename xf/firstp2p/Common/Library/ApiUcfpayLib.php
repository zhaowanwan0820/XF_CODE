<?php

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\SignatureLib;
use NCFGroup\Common\Library\AesLib;

class ApiUcfpayLib
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
        $params['merchantId'] = $this->config->merchantId;
        if(isset($params['fundMerchantId']) && $params['fundMerchantId'] === 0){
            unset($params['fundMerchantId']);
        }else{
            $params['fundMerchantId'] = $this->config->fundMerchantId;
        }
        $params['sign'] = SignatureLib::generate($params, "&key={$this->config->signatureKey}");
        $queryString = http_build_query($params, JSON_UNESCAPED_UNICODE);
        $params = array('data' => AesLib::encode($queryString, base64_decode($this->config->aesKey)));

        Logger::info("Ucfpay generateParams. queryString:{$queryString}");

        return $params;
    }

    /**
     * 验证签名
     */
    public function verify(array $params)
    {
        return SignatureLib::verify($params, "&key={$this->config->signatureKey}");
    }

    /**
     * 加密数据
     */
    public function encode($data)
    {
        $data['sign'] = SignatureLib::generate($data, "&key={$this->config->signatureKey}");
        $data = http_build_query($data);
        $data = AesLib::encode($data, base64_decode($this->config->aesKey));
        $result = json_encode(array('data' => $data));

        return $result;
    }

    /**
     * 解密数据
     */
    public function decode($data)
    {
        $responseArray = json_decode($data, true);
        $responseData = isset($responseArray['data']) ? $responseArray['data'] : '';
        $result = AesLib::decode($responseData, base64_decode($this->config->aesKey));
        parse_str($result, $resultArray);

        return $resultArray;
    }

}
