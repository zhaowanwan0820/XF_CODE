<?php
namespace libs\payment\ucfpay;

use NCFGroup\Common\Library\Idworker;
use libs\payment\IPayment;
use libs\utils\PaymentApi;
use libs\utils\Curl;
use libs\utils\Logger;
use libs\utils\Aes;
use libs\utils\Alarm;

class Ucfpay implements IPayment
{
    /**
     * 支付相关配置
     * @var array
     */
    private static $config;

    /**
     * 先锋支付请求接口超时时间（秒）
     */
    const REQ_TIMEOUT = 8;

    /**
     * token重试次数
     * @var int
     */
    const APPLY_TOKEN_RETRY_COUNT = 3;

    public function setGlobalConfig($config)
    {
       self::$config = $config;
    }

    public function request($key, $params)
    {
        //统一开关
        if (!app_conf('PAYMENT_ENABLE'))
        {
            PaymentApi::log("Ucfpay Request failed. PAYMENT_ENABLE is false. key:$key, params:".json_encode($params, JSON_UNESCAPED_UNICODE));
            return false;
        }

        \libs\utils\Monitor::add('PAYMENTAPI_REQUEST');
        \libs\utils\Monitor::add('PAYMENTAPI_REQUEST_' . $key);

        $config = $this->getConfig($key);
        if (empty($config))
        {
            // 请求无效接口记录
            return array();
        }

        //发起请求，记录请求时间
        $retryCount = empty($config['retry']) ? 1 : PaymentApi::REQUEST_API_RETRY_COUNT;

        for ($i = 0; $i < $retryCount; $i++)
        {
            if ($i > 0)
            {
                \libs\utils\Monitor::add('PAYMENTAPI_REQUEST_FAILED');
                PaymentApi::log("Ucfpay Request retry. key:$key, times:$i, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            }
            //拼接请求参数
            $requestParams = $paramsLog = $this->getParams($config, $params);
            if (empty($requestParams))
            {
                return array();
            }
            $paramsJson = json_encode($requestParams, JSON_UNESCAPED_UNICODE);
            // 脱敏记录到日志中的数据
            $this->_formatParamsForLog($paramsLog);
            $paramsJsonLog = json_encode($paramsLog, JSON_UNESCAPED_UNICODE);
            $url = $config['url'];
            PaymentApi::log("Ucfpay Request $key. url:$url, params:$paramsJsonLog");

            $requestStart = microtime(true);
            $response = Curl::post($url, $requestParams, [], self::REQ_TIMEOUT);
            $requestCost = round(microtime(true) - $requestStart, 3);
            $code = Curl::$httpCode;
            $error = Curl::$error;
            PaymentApi::log("Ucfpay Response $key. cost:{$requestCost}s, code:$code, error:$error, ret:".strip_tags($response));

            if (!empty($response))
            {
                break;
            }
        }

        //解密
        $result = Aes::decode($response, $this->aesKeyConvert($this->getConfig('common', 'AES_KEY')));
        $resultArray = $resultArrayLog = json_decode($result, true);
        // 脱敏记录到日志中的数据，[用户绑定银行卡信息查询]接口返回了cardNo银行卡号字段
        if (isset($resultArrayLog['bankCards']) && !empty($resultArrayLog['bankCards']))
        {
            $this->_formatParamsForLog($resultArrayLog['bankCards']);
        }
        $resultJsonLog = json_encode($resultArrayLog, JSON_UNESCAPED_UNICODE);
        PaymentApi::log("Ucfpay Aes decode. result:$resultJsonLog");

        if (empty($resultArray))
        {
            PaymentApi::log("Ucfpay Resquest failed. key:$key, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'Ucfpay_Request_Failed', "url:$url, code:$code, error:$error, params:$paramsJsonLog, response:$response");
            return array();
        }
        return $resultArray;
    }

    public function requestMobile($key, $params)
    {
        if (!app_conf('PAYMENT_ENABLE')) {
            PaymentApi::log("Ucfpay MobileP2p Request failed. PAYMENT_ENABLE is false. key:{$key}, params:".json_encode($params, JSON_UNESCAPED_UNICODE));
            return false;
        }
        $config = $this->getConfig($key);
        if (empty($config)) {
            return array();
        }

        //发起请求， 记录请求时间
        $retryCount = empty($config['retry']) ? 1 : PaymentApi::REQUEST_API_RETRY_COUNT;
        for ($i = 0; $i < $retryCount; ++$i) {
            if ($i > 0)
            {
                PaymentApi::log("Ucfpay MobileP2p Request retry. key:$key, times:$i, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            }
            if (empty($params))
            {
                return array();
            }
            // AES 加密
            $queryString = Aes::buildString($params);
            $aesData = '';
            // 带签名
            if ($config['withSign']) {
                $signature = md5($queryString."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
                $params['sign'] = $signature;
                $aesData = Aes::encode($queryString."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
            }
            else {
                $aesData = Aes::encode($queryString, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
            }
            // log
            $requestData = array('data' => $aesData);
            $paramsJson = json_encode($params, JSON_UNESCAPED_UNICODE);
            // 脱敏记录到日志中的数据
            $this->_formatParamsForLog($params);
            $paramsJsonLog = json_encode($params, JSON_UNESCAPED_UNICODE);
            $requestDataJson = json_encode($requestData, JSON_UNESCAPED_UNICODE);
            $url = $config['url'];
            PaymentApi::log("Ucfpay MobileP2p Request $key. url:$url, params:$paramsJsonLog, aesDATA:{$requestDataJson}");
            $requestStart = microtime(true);
            $response = Curl::post($url, $requestData);
            $requestCost = round(microtime(true) - $requestStart, 3);
            $code = Curl::$httpCode;
            $error = Curl::$error;
            PaymentApi::log("Ucfpay MobileP2p Response $key. cost:{$requestCost}s, code:$code, error:$error, ret:".strip_tags($response));

            if (!empty($response))
            {
                break;
            }
        }

        $response = json_decode($response, true);
        //解密
        $result = Aes::decode($response['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        PaymentApi::log("Ucfpay MobileP2p Aes decode. result:$result");

        if (empty($result))
        {
            PaymentApi::log("Ucfpay MobileP2p Resquest failed. key:$key, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'Ucfpay_MobileP2p_Request_Failed', "url:$url, code:$code, error:$error, params:$paramsJsonLog, response:$response");
            return array();
        }

        $result = json_decode($result, true);
        return $result;
    }

    public function getConfig($key, $subKey = '')
    {
        if (self::$config === null)
        {
            self::$config = include APP_ROOT_PATH.PaymentApi::CONFIG_FILE;
        }

        if (empty(self::$config[$key]))
        {
            return array();
        }

        if ($subKey !== '')
        {
            return isset(self::$config[$key][$subKey]) ? self::$config[$key][$subKey] : '';
        }

        return self::$config[$key];
    }

    public function getParams($config, $params)
    {
        //申请Token
        $token = $this->applyToken($config, $params);
        if (empty($token))
        {
            return array();
        }

        //拼接参数
        $params['token'] = $token;
        if (empty($params['callbackUrl']) && isset($config['callbackUrl']))
        {
            $params['callBackUrl'] = $config['callbackUrl'];
            // 兼容支付接口
            $params['callbackUrl'] = $config['callbackUrl'];
        } else if (!empty($params['callbackUrl']) && !empty($config['domain'])) { // 支持业务回调参数
            $params['callBackUrl'] = $params['callbackUrl'] = $config['domain'] . $params['callbackUrl'];
        }
        // TODO 系统配置可能替换业务参数
        if (empty($params['returnUrl']) && isset($config['returnUrl']))
        {
            $params['returnUrl'] = $config['returnUrl'];
        }

        if (isset($config['bizType']))
        {
            $params['reqBizType'] = $config['bizType'];
        }
        $params['merchantId'] = $this->getConfig('common', 'MERCHANT_ID');
        $params['theTime'] = time();
        $params['signature'] = $this->getSignature($params);
        return $params;
    }

    /**
     * 计算Signature
     * @param array $params
     * @return string
     * @see \libs\payment\IPayment::getSignature()
     */
    public function getSignature($params)
    {
        if (isset($params['signature']))
        {
            unset($params['signature']);
        }
        ksort($params);

        $paramsJoin = array();
        foreach ($params as $key => $value)
        {
            $paramsJoin[] = "$key=$value";
        }
        $paramsString = implode('&', $paramsJoin) . $this->getConfig('common', 'SIGNATURE_SALT');

        return md5($paramsString);
    }

    /**
     * 校验签名是否正确
     * @see \libs\payment\IPayment::verifySignature()
     */
    public function verifySignature($params, $signature)
    {
        $signatureLocal = $this->getSignature($params);
        return $signatureLocal === $signature;
    }

    /**
     * 获取可get方式的request url
     * @param string $key 请求的方法名称
     * @param [] $params  业务参数
     * @return string 拼接完参数的url string
     */
    public function getRequestUrl($key, $params)
    {
        $config = $this->getConfig($key);
        if (empty($config))
        {
            return '';
        }

        $params = $this->getParams($config, $params);
        if (empty($params))
        {
            return '';
        }

        $url = $config['url'];
        PaymentApi::log("Ucfpay getRequestUrl $key. url:$url, params:".json_encode($params, JSON_UNESCAPED_UNICODE));
        $queryString = http_build_query($params);
        $url .= '?'.$queryString;
        PaymentApi::log("Ucfpay getRequestUrl:$url");
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
        $config = $this->getConfig($key);
        if (empty($config))
        {
            return '';
        }

        $params = $this->getParams($config, $params);
        if (empty($params))
        {
            return '';
        }

        $url = $config['url'];
        PaymentApi::log("Ucfpay getForm $key. url:$url, params:".json_encode($params, JSON_UNESCAPED_UNICODE));

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
     * 先锋支付解密方法
     */
    public function decode($encodedString)
    {
        $data = Aes::decode($encodedString, $this->aesKeyConvert($this->getConfig('common', 'AES_KEY')));
        $data = json_decode($data, true);
        return $data;
    }

    /**
     * 回复加密数据
     * @param string $data
     * @return string
     */
    public function response($params)
    {
        $data = json_encode($params, JSON_UNESCAPED_UNICODE);
        PaymentApi::log("Ucfpay Callback response. ret:$data");

        $data = Aes::encode($data, $this->aesKeyConvert($this->getConfig('common', 'AES_KEY')));

        return $data;
    }

    /**
     * 申请Token
     */
    private function applyToken($config, $apiParams)
    {
        try{
            $token = Idworker::instance()->getId();
        } catch(\Exception $e) {
            $errorMsg = sprintf('applyToken_Idworker_getId_exception, params:%s, errorMsg:%s', json_encode($apiParams, JSON_UNESCAPED_UNICODE), $e->getMessage());
            PaymentApi::log($errorMsg, Logger::ERR);
            Alarm::push('paymentapi', 'Ucfpay_ApplyToken_failed', $errorMsg);
        }

        if (empty($token)) {
            // 获取失败则本地生成
            $token = $this->_getLocalToken($config, $apiParams);
        }

        PaymentApi::log(sprintf('getApplyToken, params:%s, token:%s', json_encode($apiParams, JSON_UNESCAPED_UNICODE), $token));
        return $token;
    }

    /**
     * 生成本地token
     * @param array $config
     * @param array $apiParams
     * @return string
     */
    private function _getLocalToken($config, $apiParams) {
        $data[] = $this->getConfig('common', 'MERCHANT_ID');
        if (!empty($config['url'])) {
            $data[] = $config['url'];
        }
        if (!empty($config['params']['userId']))
        {
            $data[] = $apiParams['userId'];
        }

        mt_srand((double) microtime() * 10000);
        // 根据当前时间（微秒计）生成唯一id
        $charid = strtoupper(md5(microtime(true) . uniqid() . mt_rand(100000, 999999)));
        $hyphen = chr(45); // "-"
        $data[] = '' . substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);
        return md5(join($hyphen, $data));
    }

    /**
     * 申请Token
     */
    private function applyTokenOld($config, $apiParams)
    {
        //获取接口Url
        $tokenConfig = empty($config['redirect']) ? $this->getConfig('tokenMerchant') : $this->getConfig('tokenUser');
        $url = $tokenConfig['url'];

        //参数拼接
        $params = array();
        $params['merchantId'] = $this->getConfig('common', 'MERCHANT_ID');
        if (!empty($config['params']['userId']))
        {
            $params['uId'] = $apiParams['userId'];
        }
        $params['signature'] = $this->getSignature($params);

        //请求, 失败重试3次
        for ($i = 0; $i < self::APPLY_TOKEN_RETRY_COUNT; $i++) {
            if ($i > 0) {
                \libs\utils\Monitor::add('PAYMENTAPI_APPLYTOKEN_FAILED');
                PaymentApi::log("Ucfpay Apply token retry. times:$i, cost:{$cost}s, code:$code, error:$error", Logger::ERR);
            }
            //请求token
            $requestStart = microtime(true);
            $response = Curl::post($url, $params, [], self::REQ_TIMEOUT);
            $requestCost = round(microtime(true) - $requestStart, 3);
            $code = Curl::$httpCode;
            $error = Curl::$error;
            PaymentApi::log("Ucfpay Apply token response. cost:{$requestCost}s, code:$code, error:$error, url:$url, params:".json_encode($params, JSON_UNESCAPED_UNICODE).", response:$response");
            if (!empty($response)) {
                break;
            }
        }

        //处理结果
        $resultArray = json_decode($response, true);
        $token = isset($resultArray['result']) ? $resultArray['result'] : '';

        if (empty($token))
        {
            PaymentApi::log("Ucfpay Apply token failed. code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'Ucfpay_ApplyToken_failed', "code:$code, error:$error, url:$url, params:".json_encode($params, JSON_UNESCAPED_UNICODE).", response:$response");
            return '';
        }

        return $token;
    }

    /**
     * AES Key转换
     */
    private function aesKeyConvert($key)
    {
        $result = '';

        $keyLen = strlen($key);
        for ($i = 0; $i < $keyLen; $i += 2)
        {
            $result .= chr('0x'.$key[$i].$key[$i + 1]);
        }

        return $result;
    }

    /**
     * 脱敏记录到日志中的数据
     * @param array $params
     */
    private function _formatParamsForLog(&$params)
    {
        if (isset($params[0]) && !empty($params[0]))
        {
            foreach ($params as &$item)
            {
                $this->_formatParamsForLog($item);
            }
        }
        // 银行卡号
        isset($params['bankCardNo']) && !empty($params['bankCardNo']) && $params['bankCardNo'] = formatBankcard($params['bankCardNo']);
        isset($params['accountNo']) && !empty($params['accountNo']) && $params['accountNo'] = formatBankcard($params['accountNo']);
        // 身份证号
        isset($params['cardNo']) && !empty($params['cardNo']) && $params['cardNo'] = idnoFormat($params['cardNo']);
        isset($params['agentPersonNo']) && !empty($params['agentPersonNo']) && $params['agentPersonNo'] = idnoFormat($params['agentPersonNo']);
        isset($params['coperationCard']) && !empty($params['coperationCard']) && $params['coperationCard'] = idnoFormat($params['coperationCard']);
        // 手机号
        isset($params['phone']) && !empty($params['phone']) && $params['phone'] = format_mobile($params['phone']);
        isset($params['newPhone']) && !empty($params['newPhone']) && $params['newPhone'] = format_mobile($params['newPhone']);
        isset($params['phoneNo']) && !empty($params['phoneNo']) && $params['phoneNo'] = format_mobile($params['phoneNo']);
        isset($params['agentPersonPhone']) && !empty($params['agentPersonPhone']) && $params['agentPersonPhone'] = format_mobile($params['agentPersonPhone']);
        isset($params['coperationCell']) && !empty($params['coperationCell']) && $params['coperationCell'] = format_mobile($params['coperationCell']);
    }
}