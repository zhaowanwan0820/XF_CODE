<?php
namespace libs\payment\unitebank;

use libs\payment\IPayment;
use libs\utils\PaymentApi;
use libs\utils\Curl;
use libs\utils\Logger;
use libs\utils\Aes;

class Unitebank implements IPayment
{
    /**
     * 相关配置
     * @var array
     */
    private static $config;

    /**
     * 参数为空的错误码
     * @var int
     */
    const ERROR_REQUIRED_PARAMETER_EMPTY = 1801;
    /**
     * 返回包格式错误的错误码
     * @var int
     */
    const ERROR_RESPONSE_DATA_INVALID = 1802;
    /**
     * 网络错误, 偏移量1900, 详见 http://curl.haxx.se/libcurl/c/libcurl-errors.html
     * @var int
     */
    const ERROR_RESPONSE_NETWORK = 1900;
    /**
     * 网络访问错误提示
     * @var string
     */
    const ERROR_NETWORK_MESSAGE = '网络超时，请重试！';

    public $newSslProvider = false;

    /**
     * 请求的Header信息
     * @var array
     */
    private $headers = array('Accept:application/json');

    public function setGlobalConfig($config)
    {
       self::$config = $config;
    }

    public function request($key, $params)
    {
        //统一开关
        if (!app_conf('PAYMENT_ENABLE'))
        {
            PaymentApi::log("UniteBank Request failed. PAYMENT_ENABLE is false. key:$key, params:".json_encode($params));
            return false;
        }

        if ($key === 'CreateLoanAcctPre')
        {
            $this->newSslProvider = true;
        }

        \libs\utils\Monitor::add('PAYMENTAPI_UNITEBANK_REQUEST');
        \libs\utils\Monitor::add('PAYMENTAPI_UNITEBANK_REQUEST_' . $key);

        $config = $this->getConfig($key);
        if (empty($config))
        {
            // 请求无效接口记录
            return array(
               'ret' => false,
               'error_code' => self::ERROR_REQUIRED_PARAMETER_EMPTY,
               'error_msg' => 'config is empty',
               'http_code' => 200,
               'result' => array(),
            );
        }

        //发起请求，记录请求时间
        $retryCount = empty($config['retry']) ? 1 : PaymentApi::REQUEST_API_RETRY_COUNT;

        for ($i = 0; $i < $retryCount; $i++)
        {
            if ($i > 0)
            {
                \libs\utils\Monitor::add('PAYMENTAPI_UNITEBANK_REQUEST_FAILED');
                PaymentApi::log("UniteBank Request retry. key:$key, times:$i, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            }
            //拼接请求参数
            $params = $paramsLog = $this->getParams($config, $params);
            if (empty($params))
            {
                return array(
                   'ret' => false,
                   'error_code' => self::ERROR_REQUIRED_PARAMETER_EMPTY,
                   'error_msg' => 'params is empty',
                   'http_code' => 200,
                   'result' => array(),
                );
            }
            $paramsJson = json_encode($params, JSON_UNESCAPED_UNICODE);
            // 脱敏记录到日志中的数据
            $this->_formatParamsForLog($paramsLog);
            $paramsJsonLog = json_encode($paramsLog, JSON_UNESCAPED_UNICODE);
            $url = $config['url'];
            PaymentApi::log("UniteBank Request $key. url:$url, params:$paramsJsonLog");

            $requestStart = microtime(true);
            $response = Curl::post($url, $params, $this->headers);
            $requestCost = round(microtime(true) - $requestStart, 3);
            $code = Curl::$httpCode;
            $error = Curl::$error;
            PaymentApi::log("UniteBank Response $key. cost:{$requestCost}s, code:$code, error:$error, ret:".strip_tags($response));

            if (!empty($response))
            {
                break;
            }
        }

        // 检查请求时，是否有报错
        if (!empty($error) || $code != 200)
        {
            PaymentApi::log("UniteBank Resquest failed. Response Or HttpCode Is Error,key:$key, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'UniteBank_Request_Failed', "url:$url, code:$code, error:$error, params:$paramsJson, response:$response");
            return array(
                'ret' => false,
                'error_code' => self::ERROR_RESPONSE_NETWORK + $this->httpErrno,
                'error_msg' => self::ERROR_NETWORK_MESSAGE,
                'http_code' => $code,
                'result' => array(),
            );
        }

        //解析Json数据并校验返回数据的签名
        $resultData = $this->_parseJsonData($response);
        PaymentApi::log("UniteBank Rsa decode. result:$response");

        if (empty($resultData))
        {
            PaymentApi::log("UniteBank Resquest failed. key:$key, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'UniteBank_Request_Failed', "url:$url, code:$code, error:$error, params:$paramsJsonLog, response:$response");
            return array(
                'ret' => false,
                'error_code' => self::ERROR_RESPONSE_DATA_INVALID,
                'error_msg' => self::ERROR_NETWORK_MESSAGE,
                'http_code' => $code,
                'result' => array(),
            );
        }

        return array(
            'ret' => true,
            'http_code' => $code,
            'result' => $resultData,
        );
    }

    public function requestMobile($key, $params)
    {
        return false;
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
        // 合并接口默认参数
        if (isset($config['params'])) $params = array_merge($params, $config['params']);
        // 必填签参数校验
        $requiredFields = $config['requiredFields'];
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

        // 必验签参数校验
        $signParams = array();
        $signFields = $config['signFields'];
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
        //拼接参数
        $params['Sign'] = $this->getSignature($signParams);
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
        $signatureData = $this->getSignatureData($params);
        PaymentApi::log("UniteBank getSignature, signParams:$signatureData");
        $rsa = new \libs\encrypt\RSA;
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $providerName = 'MERCHANT_PRIVKEY';
        if ($this->newSslProvider)
        {
            $providerName = 'UB_PRIKEY';
        }
        $rsa->loadKey($this->getConfig('common', $providerName));
        $rsaSign = $rsa->sign($signatureData);
        $signature = $this->_strToHex($rsa->sign($signatureData));
        return $signature;
    }

    /**
     * 获取待签名数据
     */
    public function getSignatureData($params)
    {
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

    /**
     * 校验签名是否正确
     * @see \libs\payment\IPayment::verifySignature()
     */
    public function verifySignature($params, $signature)
    {
        $rsa = new \libs\encrypt\RSA;
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $signatureData = $this->getSignatureData($params);
        $rsa->loadKey($this->getConfig('common', 'UB_PUBKEY'));
        $signature = $this->_hexToStr($signature);
        return $rsa->verify($signatureData, $signature);
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
        PaymentApi::log("UniteBank getRequestUrl $key. url:$url, params:".json_encode($params));
        $queryString = http_build_query($params);
        $url .= '?'.$queryString;
        PaymentApi::log("UniteBank getRequestUrl:$url");
        return $url;
    }

    /**
     * 获取可提交的表单
     * @param string $key 接口关键字，参考paymentapi.conf.php
     * @param array $params 参数数组
     * @param string $formId Form的DOM结点ID
     * @param boolen $targetNew 表单是否新窗口打开
     * @param boolen $quickDebug 表单调试
     * @return string
     */
    public function getForm($key, $params, $formId = 'paymentapi_form', $targetNew = true, $quickDebug = false)
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
        PaymentApi::log("UniteBank getForm $key. url:$url, params:".json_encode($params));

        $target = $targetNew ? "target='blank'" : '';
        $html = "<form action='$url' id='$formId' $target style='display:none;' method='post'>\n";
        if ($quickDebug)
        {
            $html = "<form action='$url' id='$formId' $target style='display:;' method='post'>\n";
            $html .= '<input type="submit" name="commit" value="commit"/>';
        }
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";

        return $html;
    }

    /**
     * 回复数据
     * @param string $data
     * @return string
     */
    public function response($params)
    {
        $data = json_encode($params, JSON_UNESCAPED_UNICODE);
        PaymentApi::log("UniteBank Callback response. ret:$data");
        return $data;
    }

    /**
     * 解析Json数据并校验返回数据的签名
     * @param array $response
     * @return boolean|array
     */
    private function _parseJsonData($response)
    {
        $return = json_decode($response, true);
        // 远程返回的不是 json 格式, 说明返回包有问题
        if (is_null($return))
        {
            return false;
        }

        // 接口返回错误信息
        if (array_key_exists('jsonError', $return))
        {
            PaymentApi::log("UniteBank Response failed. key:$key, code:$code, error:$error, response:$response", Logger::ERR);
            return $return;
        }
        // 校验签名
        $vefiryRet = $this->verifySignature($return, $return['Sign']);
        if (!$vefiryRet)
        {
            PaymentApi::log("UniteBank Response failed. key:$key, code:$code, error:$error, response:$response", Logger::ERR);
            return false;
        }
        return $return;
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
        // 银行卡号
        isset($params['AcNo']) && !empty($params['AcNo']) && $params['AcNo'] = formatBankcard($params['AcNo']);
        // 身份证号
        isset($params['IdNo']) && !empty($params['IdNo']) && $params['IdNo'] = idnoFormat($params['IdNo']);
        // 手机号
        isset($params['MobilePhone']) && !empty($params['MobilePhone']) && $params['MobilePhone'] = format_mobile($params['MobilePhone']);
    }
}
