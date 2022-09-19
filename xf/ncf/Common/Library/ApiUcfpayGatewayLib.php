<?php
/**
 *  先锋支付安全网关对接
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\SignatureLib;
use NCFGroup\Common\Library\AesLib;
use NCFGroup\Common\Library\RsaLib;
use NCFGroup\Common\Library\services\UcfpayGateway;
use NCFGroup\Common\Library\StandardApi as Api;
use NCFGroup\Common\Library\ApiBase;

class ApiUcfpayGatewayLib extends ApiBase
{

    // 网关通用配置信息
    protected $config = null;
    // 将接口定义挪到类中，降低配置文件大小
    protected $services = null;

    // 强制使用http raw post 方式传递参数, 将来替换掉通过header 判断multipart/form-data 字符串的方式
    public $useHttpRawPost = true;

    public $specialHeadersTmpl = [
        'content-type: application/x-www-form-urlencoded;charset=UTF-8',
    ];
    public $specialHeaders = [];

    public $curlOpts = [
        CURLOPT_FAILONERROR => false,
    ];

    protected $merchantKeys = [];

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
        if (!$key)
        {
            throw new \Exception('无效的业务名称');
        }
        $this->specialHeaders = $this->specialHeadersTmpl;
        $service = $this->services->get($key);
        // 构建请求先锋支付的消息体
        $requestBody = [];
        // 补全默认值
        if (!empty($service['defaults'])) {
            foreach ($service['defaults'] as $keyName => $defaultValue)
            {
                if (isset($params[$keyName]))
                {
                    continue;
                }
                $params[$keyName] = $defaultValue;
            }
        }
        //拼接参数
        // noticeUrl  默认配置了通知地址 但是业务还没传递通知地址的时候,使用拼装的通知地址
        if (!empty($service['noticeUrl']) && empty($params['noticeUrl']))
        {
            $params['noticeUrl'] = $this->getNotifyDomain().$service['noticeUrl'];
        }

        // returnUrl 默认配置了 跳转地址 但是业务还没传递 跳转地址的时候,使用拼装的 跳转地址
        if (!empty($service['returnUrl']) && empty($params['returnUrl']))
        {
            $params['returnUrl'] = $this->getReturnDomain().$service['returnUrl'];
        }

        // 补全系统级参数
        $requestBody['merchantId'] = $this->services->getConfig('defaultMerchantId');
        if (!empty($params['merchantId']))
        {
            $requestBody['merchantId'] = $params['merchantId'];
        }
        // 如果传递的参数中包含商户号,则启用多商户号秘钥获取机制
        if (!empty($requestBody['merchantId']))
        {
            $this->merchantKeys = $this->getMerchantKeys($requestBody['merchantId']);
        }
        $requestBody['version']     = isset($params['version']) ? $params['version'] : $this->services->getConfig('version');
        $requestBody['service']     = $service['service'];
        // 打印脱敏请求数据
        $this->filterLog($params, $key);

        //生成随机密钥
        $aesKey = uniqid();

        // data 加密
        $requestBody['data']        = AesLib::EncodeWithOpenssl(json_encode($params, JSON_UNESCAPED_UNICODE), $aesKey);
        $requestBody['tm']          = RsaLib::publicEncrypt($aesKey, $this->getServices()->getConfig('platformPublicKey'));
        //生成数据签名
        $requestBody['sign']        = $this->getSignature($requestBody);
        $this->filterLog($requestBody);
        return http_build_query($requestBody);
    }

    /**
     *  生成签名
     */
    public function getSignature($requestBody, $getRawSignData = false)
    {
        $paramsString = '';

        ksort($requestBody);

        if (isset($requestBody['sign']))
        {
            unset($requestBody['sign']);
        }

        $paramsString = http_build_query($requestBody);
        $paramsString = urldecode($paramsString);
        if ($getRawSignData)
        {
            return $paramsString;
        }
        // 对拼装的 url 格式数据进行 md5签名
        $this->getMerchantKeys($requestBody['merchantId']);
        $signature = RsaLib::Sign($paramsString, $this->merchantKeys['merchantPrivateKey']);
        return $signature;
    }

    /**
     * 验证签名
     */
    public function verify($response)
    {
        // TODO 异步通知需要实现
        if (!isset($response['sign']))
        {
            throw new \Exception('Response data format cannot recognized.');
        }

        $rawSignString = $this->getSignature($response, true);

        if (!RsaLib::Verify($rawSignString, $response['sign'], $this->services->getConfig('platformPublicKey')))
        {
            throw new \Exception('Signature failed');
        }
        return true;
    }

    /**
     * 加密数据
     * @param string dataString 加密的明文数据
     * @param string $withWhichUser 指定用户证书
     * @return string 加密后数据
     */
    public function encrypt($dataString, $withWhichKey = 'platformPublicKey')
    {
        return AesLib::EncodeWithOpenssl($dataString, $this->services->getConfig($withWhichKey));
    }

    /**
     * 解密数据
     * @param string $encryptString 加密数据
     * @param string $withWhichUser  指定用户证书
     * @return string 解密后的数据
     */
    public function decrypt($response, $withWhichKey= 'merchantPrivateKey')
    {
        $response = json_decode($response, true);
        $this->verify($response);
        $code = $response["code"];
        if(UcfpayGateway::RESPONSE_SUCCESS != $code)
        {
            throw new \Exception($response['message']);
        }
        $tm     = $response["tm"];
        $data   = $response["data"];
        $this->getMerchantKeys($response['merchantId']);
        $aesKey = RsaLib::PrivateDecrypt($tm,$this->merchantKeys['merchantPrivateKey']);
        $data = AesLib::DecodeWithOpenssl($data, $aesKey);
        Api::instance($this->getGateway())->log($data, 'INFO');
        return json_decode($data, true);
    }

    public function response($msg)
    {
        return $msg;
    }

}
