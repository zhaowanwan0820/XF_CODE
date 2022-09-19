<?php
/**
 *  海口联合农商银行接口对接
 */

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\ApiBase;
use NCFGroup\Common\Library\AesLib;
use NCFGroup\Common\Library\RsaLib;
use NCFGroup\Common\Library\services\Supervision;
use NCFGroup\Common\Library\StandardApi;
use NCFGroup\Common\Library\encrypt\RSA;

class ApiUniteBankLib extends ApiBase
{
    // 网关通用配置信息
    protected $config = null;
    // 将接口定义挪到类中，降低配置文件大小
    protected $services = null;

    public $specialHeaders = ['Accept:application/json'];

    public function __construct($config, $services)
    {
        $this->config = $config;
        $this->services = $services;
    }

    /**
     * 拼接参数
     * @param array $params 业务参数
     * @return array 拼接好的参数
     */
    public function generateParams(array $params, $key = null)
    {
        if (empty($key))
        {
            throw new \Exception('无效的业务名称', Supervision::RESPONSE_CODE_FAILURE);
        }
        $service = $this->services->get($key);
        // 构建请求存管的消息体
        $requestBody = [];

        // 合并接口默认参数
        if (isset($service['params'])) $params = array_merge($params, $service['params']);
        // 必填签参数校验
        $requiredFields = $service['required'];
        $fieldNames = array_keys($requiredFields);
        if (!empty($fieldNames))
        {
            foreach ($fieldNames as $fieldName)
            {
                if (!array_key_exists($fieldName, $params) || $params[$fieldName] === '')
                {
                    throw new \Exception('必填参数'.$fieldName.'不存在或者值空');
                }
            }
        }
        $params['WXUrl'] = isset($params['WXUrl']) && isset($params['notifyDomain']) ? $params['notifyDomain'].$params['WXUrl'] : '';
        $params['WXLoanUrl'] = isset($params['WXLoanUrl']) && isset($params['notifyDomain'])  ? $params['notifyDomain'].$params['WXUrl'] : '';
        $params['WXRepayUrl'] = isset($params['WXRepayUrl']) && isset($params['notifyDomain'])  ? $params['notifyDomain'].$params['WXUrl'] : '';
        $params['WXGrantUrl'] = isset($params['WXGrantUrl']) && isset($params['notifyDomain'])  ? $params['notifyDomain'].$params['WXUrl'] : '';
        unset($params['notifyDomain']);
        if (empty($params['WXUrl'])) unset($params['WXUrl']);
        if (empty($params['WXLoanUrl'])) unset($params['WXLoanUrl']);
        if (empty($params['WXRepayUrl'])) unset($params['WXRepayUrl']);
        if (empty($params['WXGrantUrl'])) unset($params['WXGrantUrl']);

        $signParams = array();
        $signFields = $service['sign'];
        $fieldNames = array_keys($signFields);
        if (!empty($fieldNames))
        {
            foreach ($fieldNames as $fieldName)
            {
                if (!array_key_exists($fieldName, $params) || $params[$fieldName] === '')
                {
                    throw new \Exception('必须参与签名参数'.$fieldName.'不存在或者值空');
                }
                $signParams[$fieldName] = $params[$fieldName];
            }
        }

        // 生成签名
        $params['signature'] = $this->getSignature($signParams);

        return $params;
    }

    /**
     *  生成签名
     */
    public function getSignature($params, $getRawSignData = false)
    {
        $paramsString = $this->getParamsString($params);
        if ($getRawSignData)
        {
            return $paramsString;
        }
        $rsa = new RSA;
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $rsa->loadKey($this->services->getConfig('merchantPrivateKey'));
        $rsaSign = $rsa->sign($paramsString);
        $signature = $this->_strToHex($rsa->sign($paramsString));

        return $signature;
    }

    /**
     * 验证签名
     */
    public function verifySignature($response,$signature)
    {
        $rsa = new RSA;
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $signatureData = $this->getParamsString($response);
        $rsa->loadKey($this->services->getConfig('platformPublicKey'));
        $signature = $this->_hexToStr($signature);
        return $rsa->verify($signatureData, $signature);
    }

    /**
     * 加密数据
     * @param string dataString 加密的明文数据
     * @param string $withWhichUser 指定用户证书
     * @return string 加密后数据
     */
    public function encrypt($dataString, $withWhichKey = 'platformPublicKey')
    {
        return $dataString;
    }

    /**
     * 解密数据
     * @param string $response 加密数据
     * @param boolean $verify 是否校验签名
     * @return string 解密后的数据
     */
    public function decrypt($response, $verify = false)
    {
        return $response;
    }

    public function response($params)
    {
        $data = json_encode($params, JSON_UNESCAPED_UNICODE);
        StandardApi::instance($this->getGateway())->log("UniteBank Callback response. ret:$data");
        return $data;
    }

    /**
     * 获取待签名的原数据
     * @param array $params
     * @return string
     */
    public function getParamsString($params) {
        if (isset($params['Sign']))
        {
            unset($params['Sign']);
        }
        ksort($params);

        $paramsJoin = array();
        foreach ($params as $key => $value)
        {
            !is_null($value) && $value !== 'null' && $paramsJoin[] = "$key=$value";
        }
        $paramsString = implode('&', $paramsJoin);
        return $paramsString;
    }

    private function _strToHex($string)
    {
        $hex = '';
        for ($i=0; $i<strlen($string); $i++)
        {
            $tmp = dechex(ord($string[$i]));
            $hex .= strlen($tmp) < 2 ? '0' . $tmp : $tmp;
        }
        $hex = strtolower($hex);
        return $hex;
    }

    private function _hexToStr($hex)
    {
        $string = '';
        for ($i=0; $i<strlen($hex)-1; $i+=2)
        {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }
}
