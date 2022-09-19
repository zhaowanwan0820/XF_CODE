<?php
/**
 *  先锋支付安全网关对接抽象类
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\StandardApi;

/**
 * 抽象的接口服务
 */
abstract class ApiBase
{
    /**
     * 当前网关名称
     */
    protected $gateway = '';

    /**
     * @var DI 具体某个接口服务的通用配置信息
     */
    protected $config = null;

    /**
     * @var object 具体某个接口服务的接口定义信息
     */
    protected $services = null;

    /**
     * @var array 特殊系统网络请求header模板
     */
    public $specialHeadersTmpl = [
        'content-type: application/x-www-form-urlencoded;charset=UTF-8',
    ];

    /**
     * @var bool 是否强制使用HTTP Raw Data 传输数据
     */
    public $useHttpRawPost = false;

    /**
     * @var array 特殊系统网络请求header
     */
    public $specialHeaders = [];

    /**
     * @var array 特定curl参数设置数组
     */
    public $curlOpts = [
        CURLOPT_FAILONERROR => false,
    ];

    /**
     * @var array 商户秘钥配置
     */
    protected $merchantKeys = [];

    public function __construct($config, $services)
    {
        $this->config = $config;
        $this->services = $services;
    }

    /**
     * 返回该api配置类
     * @return object services
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * 返回该api已经对接服务
     * @return array service list
     */
    public function getServiceList()
    {
        return $this->services->getServices();
    }

    /**
     * 设置通知根域名,如果没有显示声明,则使用当前域名作为回调跟域名
     * @param string $domain
     * @return
     */
    public function setNotifyDomain($domain)
    {
        $this->notifyDomain = $domain;
    }

    public function getNotifyDomain()
    {
        if (empty($this->notifyDomain))
        {
            $this->notifyDomain = $this->getCurrentDomain();
        }
        return $this->notifyDomain;
    }

    public function setReturnDomain($domain)
    {
        $this->returnDomain = $domain;
    }

    public function getReturnDomain()
    {
        if (empty($this->returnDomain))
        {
            $this->returnDomain = $this->getCurrentDomain();
        }
        return $this->returnDomain;
    }

    public function getCurrentDomain()
    {
        $httpHost = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : gethostname();
        if (empty($httpHost)) {
            throw new \Exception('You must use setNotifyDomain/setReturnDomain to set domain first.');
        }
        if (!empty($_SERVER['HTTP_XHTTPS']) && 1 == $_SERVER['HTTP_XHTTPS']) {
            return 'https://' . $httpHost;
        } else {
            $http = (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
            return $http . $httpHost;
        }
    }

    /**
     * 拼接参数
     * @param array $params 业务参数
     * @return array 拼接好的参数
     */
    abstract function generateParams(array $params, $key = null);

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
     *  日志敏感字段过滤
     */
    public function filterLog($data, $keyName = '')
    {
        if (gettype($data) == 'array')
        {
            foreach ($data as $key => &$val)
            {
                if (in_array($key, $this->config['filters']->toArray()))
                {
                    $len = strlen($val);
                    $val = substr($val,0,4).'****'.substr($val, $len-4, 4);
                }
            }
        }
        $logContent = json_encode($data, JSON_UNESCAPED_UNICODE);
        $classPath = explode('\\', __CLASS__);
        StandardApi::instance($this->getGateway())->log(sprintf('%s_%s, key:%s, params:%s', end($classPath), __FUNCTION__, $keyName, $logContent), 'INFO');
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
        StandardApi::instance('ucfpayGateway')->log($data, 'INFO');
        return json_decode($data, true);
    }

    public function getMerchantKeys($merchantId)
    {
        $keys = $this->getServices()->getConfig('merchantKeys');
        $this->merchantKeys = isset($keys[$merchantId]) ? $keys[$merchantId] : false;
        if ($this->merchantKeys === false)
        {
            throw new \Exception('invalid merchant keys config with merchantId:'.$merchantId);
        }
        return $this->merchantKeys;
    }

    public function response($msg)
    {
        return $msg;
    }

    public function setGatewayName($gateway)
    {
        $this->gateway = $gateway;
    }

    public function getGateway()
    {
        return $this->gateway;
    }

}
