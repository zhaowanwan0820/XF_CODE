<?php

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\LoggerRemoteLib as RemoteLogger;
use Phalcon\Config;

class StandardApi
{
    const UCFPAY_GATEWAY = 'ucfpayGateway';

    const SUPERVISION_GATEWAY = 'supervision';

    const UNITEBANK_GATEWAY = 'uniteBank';

    //接口重试次数
    const REQUEST_API_RETRY_COUNT = 3;

    //请求超时时间(秒)
    const CURL_POST_TIMEOUT = 10;

    private static $instance = array();

    /**
     * 单例化
     */
    public static function instance($apiType)
    {
        if (!isset(self::$instance[$apiType]))
        {
            self::$instance[$apiType] = new self($apiType);
        }

        return self::$instance[$apiType];
    }

    private $api = null;

    private $config = array();

    private $logId = null;

    private $monitor = null;

    private $conf = [
        'api' => [
        'ucfpayGateway' => array(
            // 新版本接口定义通过接口类实现
            'serviceClass'  => 'UcfpayGateway',
            'filters'       => array(
                'accountNo',
            ),
        ),
        'supervision' => array(
            // 新版本接口定义通过接口类实现
            'serviceClass'  => 'Supervision',
            // 接口敏感字段过滤
            'filters'       => array(
                'certNo',
                'bankCardNo',
            ),
        ),
        'uniteBank' => array(
            // 新版本接口定义通过接口类实现
            'serviceClass'  => 'UniteBank',
        ),

        ],
    ];

    private function __construct($apiType)
    {
        $this->config = getDI()->getConfig();
        $localConfig = new Config($this->conf);
        // 合并配置文件
        $this->config->merge($localConfig);
        $this->config = $this->config->api->$apiType;
        $className = '\NCFGroup\Common\Library\Api'.ucfirst($apiType).'Lib';
        if ($this->config->serviceClass)
        {
            $serviceClass = '\NCFGroup\Common\Library\services\\'.$this->config->serviceClass;
            $services = new $serviceClass;
        }
        $this->initRemoteLogger($services);
        $this->api = new $className($this->config, $services);
        $this->api->setGatewayName($apiType);
    }

    public function getGatewayApi() {
        return $this->api;
    }

    /**
     * 初始化远程日志系统
     */
    private function initRemoteLogger($services)
    {
        $config = new \stdClass();
        $config->ip         = $services->getConfig('logServerIp');
        $config->port       = $services->getConfig('logPort');
        $config->errorlog   = $services->getConfig('logError');
        $this->logger = new RemoteLogger($config);
    }

    /**
     * 关联统一日志id
     */
    public function setLogId($logId)
    {
        if (!empty($this->logId)) {
            return ;
        }
        $this->logId = $logId ?: sprintf('%x', (intval(microtime(true) * 10000) % 864000000) * 10000 + mt_rand(0, 9999));
    }

    /**
     * 设置Monitor
     */
    public function setMonitor($monitor)
    {
        if (!empty($this->monitor)) {
            return ;
        }
        $this->monitor = $monitor;
    }

    /**
     * 新增Monitor打点
     */
    public function addMonitor($key, $count = 1)
    {
        if (!empty($this->monitor)) {
            return $this->monitor->addMonitor($key, $count);
        }
        return false;
    }

    /**
     * 集成远程日志功能
     */
    public function log($content, $level = 'INFO')
    {
        if (!is_string($content))
        {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        }

        $content = strtr($content, "\n", ' ');

        $backtrace = debug_backtrace();
        $file = isset($backtrace[1]['file']) ? basename($backtrace[1]['file']) : '';
        $line = isset($backtrace[1]['line']) ? $backtrace[1]['line'] : '';

        list($msec, $sec) = explode(' ', microtime(), 2);
        $currentTime = date('i:s', $sec).'.'.intval($msec * 1000);
        $this->logger->write("[$currentTime] [$this->logId] [$level] [{$file}:{$line}] {$content}", $level);
    }

    /**
     * 接口请求
     * @param string $key 接口关键字
     * @param array $params 参数数组
     * @param array $specialHeaders 特殊头信息参数数组
     * @return array
     */
    public function request($key, array $params, $specialHeaders = [])
    {
        $this->addMonitor('SUPERVISION_REQUEST');
        $this->addMonitor('SUPERVISION_REQUEST_' . $key);

        $url = $this->getRequestGateway($key);
        if (empty($url)) {
            $this->log('StandardApi_Request invalid gateway URI. key:'.$key, 'ERR');
            throw new \Exception('Request invalid URI');
        }

        //发起请求
        $service = $this->api->getServices()->get($key);
        $retryCount = empty($service['retry']) ? self::REQUEST_API_RETRY_COUNT : 1;
        for ($i = 0; $i < $retryCount; $i++)
        {
            if ($i > 0) {
                $this->addMonitor('SUPERVISION_REQUEST_FAILED');
                $this->log("StandardApi_Request retry. key:{$key}, url:{$url}, times:{$i}, cost:{$cost}s, code:{$httpCode}, error:{$error}", 'ERR');
            }

            $requestParams = $this->api->generateParams($params, $key);
            $this->log("StandardApi_Request. key:{$key}, url:{$url}, params:".json_encode($requestParams), 'INFO');

            $response = $this->post($url, $requestParams, $error, $httpCode, $cost, $specialHeaders);
            $this->log("StandardApi_Response. key:{$key}, cost:{$cost}s, code:{$httpCode}, error:{$error}, ret:{$response}", 'INFO');

            if (!empty($response)) {
                break;
            }
        }

        if (empty($response) || strpos($response ,'resCode') > 0) {
            $this->log("StandardApi_Request failed. key:{$key}, code:{$httpCode}, error:{$error}, response:{$response}", 'ERR');
            throw new \Exception('Response is empty or received invalid response format');
        }
        //解密结果
        $response = $this->decode($response);
        if (empty($response)) {
            $this->log("StandardApi_Request failed. Response decode failed. key:{$key}, code:{$httpCode}, error:{$error}, url:{$url}", 'ERR');
            throw new \Exception('Response decode failed');
        }
        $this->log("StandardApi_Request success. key:{$key}, url:{$url}, ret:".json_encode($response, JSON_UNESCAPED_UNICODE), 'INFO');
        return $response;
    }

    /**
     * 回复加密数据
     * @param mixed $params
     * @return string
     */
    public function response($response)
    {
        $logInfo = $response;
        if (is_array($response))
        {
            $logInfo = json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        $this->log("StandardApi_Callback response. ret:".$logInfo, 'INFO');

        return $this->api->response($response);
    }

    /**
     * 验证参数签名
     */
    public function verify(array $params)
    {
        return $this->api->verify($params);
    }

    /**
     * POST请求
     */
    private function post($url, $params, &$error, &$httpCode, &$cost, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);

        $isMultipartFormData = false;
        foreach ($headers as $header) {
            if (stripos($header, 'multipart/form-data') !== false) {
                $isMultipartFormData = true;
                break;
            }
        }

        $isMultipartFormData = $isMultipartFormData || $this->api->useHttpRawPost;
        if ($isMultipartFormData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_POST_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (substr($url, 0, 5) === 'https')
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
        }
        // 干死某些不要脸的服务提供者，application/z-xxxxx 是啥
        if (!empty($this->api->specialHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->api->specialHeaders);
        }
        // 特殊定制的 CURL OPTs
        if (!empty($this->api->curlOpts))
        {
            foreach ($this->api->curlOpts as $opt => $val)
            {
                curl_setopt($ch, $opt, $val);
            }
        }
        $requestStart = microtime(true);
        $result = curl_exec($ch);

        $cost = round(microtime(true) - $requestStart, 3);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $result;
    }

    /**
     * 拼接请求服务地址
     * @param string $key
     */
    public function getRequestGateway($key)
    {
        $gatewayUrl = $this->api->getServices()->getConfig('gatewayUrl') ? $this->api->getServices()->getConfig('gatewayUrl') : '';
        // 总有网关一个配置地址一个入口
        $service = $this->api->getServices()->get($key);
        if (!empty($service['apiName']))
        {
          $gatewayUrl .= $service['apiName'];
        }

        if (empty($gatewayUrl)) {
            $this->log('StandardApi_Request invalid gateway for service name :'.$key, 'ERR');
            throw new \Exception('Request invalid gateway.');
        }
        return $gatewayUrl;
    }

    /**
     *  解析接口返回结果，返回对应的结果
     *
     * @param array $response 业务响应数据
     * @param boolean $verify 是否校验签名
     */
    public function decode($response, $verify = false)
    {
        return $this->api->decrypt($response, $verify);
    }

    /**
     * 获取可get方式的request url
     * @param string $key 请求的方法名称
     * @param [] $params  业务参数
     * @return string 拼接完参数的url string
     */
    public function getRequestUrl($key, $params)
    {
        $params = $this->api->generateParams($params, $key);
        if (empty($params))
        {
            return '';
        }

        $url = $this->getRequestGateway($key);
        $queryString = http_build_query($params);
        $url .= '?'.$queryString;
        $this->log("StandardApi_getRequestUrl key:{$key}. url:{$url}, params:".json_encode($params, JSON_UNESCAPED_UNICODE), 'INFO');
        return $url;
    }

    /**
     * 获取可提交的表单
     * @param string $key 接口关键字，参考paymentapi.conf.php
     * @param array $params 参数数组
     * @param string $formId Form的DOM结点ID
     * @param boolen $targetNew 表单是否新窗口打开
     * @return string
     */
    public function getForm($key, $params, $formId = 'paymentapi_form', $targetNew = true)
    {
        $params = $this->api->generateParams($params, $key);
        if (empty($params))
        {
            return '';
        }

        $url = $this->getRequestGateway($key);
        $this->log("Supervision getForm {$key}. url:{$url}, params:".json_encode($params, JSON_UNESCAPED_UNICODE), 'INFO');

        $target = $targetNew ? "target='blank'" : '';

        $html = "<form action='$url' id='$formId' $target style='display:none;' method='post'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";

        return $html;
    }

    /**
     * pretty show services list
     */
    public function showServiceList()
    {
        print_r($this->api->getServiceList());
    }

    public function setNotifyDomain($domain)
    {
        $this->api->setNotifyDomain($domain);
    }

    public function setReturnDomain($domain)
    {
        $this->api->setReturnDomain($domain);
    }

    /**
     * 获取商户号
     */
    public function getMerchantId()
    {
        return $this->api->getServices()->getConfig('merchantId');
    }
}
