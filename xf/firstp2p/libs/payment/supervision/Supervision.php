<?php
namespace libs\payment\supervision;

use libs\payment\IPayment;
use libs\utils\PaymentApi;
use libs\utils\Curl;
use libs\utils\Logger;
use libs\encrypt\Rijndael;
use libs\encrypt\AES;
use libs\encrypt\DES;
use libs\encrypt\Hash;
use libs\encrypt\RSA;
use libs\encrypt\TripleDES;
use libs\math\BigInteger;
use libs\common\ErrCode;
use core\service\SupervisionBaseService as SupervisionBase;
use NCFGroup\Common\Library\Idworker;
use libs\common\WXException;

class Supervision implements IPayment
{
    /**
     * 支付相关配置
     * @var array
     */
    private static $config;

    /**
     * token重试次数
     * @var int
     */
    const APPLY_TOKEN_RETRY_COUNT = 3;

    /**
     * 记录到日志里的参数数组
     * @var array
     */
    private $paramsLog = [];

    //请求超时秒数
    private static $timeOut = 3;

    public function setGlobalConfig($config)
    {
       self::$config = $config;
    }

    public function setTimeOut($timeOut)
    {
        self::$timeOut = (int)$timeOut;
    }

    public function request($key, $params)
    {
        \libs\utils\Monitor::add('SUPERVISION_REQUEST');
        \libs\utils\Monitor::add('SUPERVISION_REQUEST_' . $key);

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
                \libs\utils\Monitor::add('SUPERVISION_REQUEST_FAILED');
                PaymentApi::log("Supervision Request retry. key:{$key}, times:{$i}, cost:{$requestCost}s, code:{$code}, error:{$error}", Logger::ERR);
            }
            //拼接请求参数
            $requestParams = $this->getParams($config, $params);
            if (empty($requestParams))
            {
                return array();
            }
            // 脱敏记录到日志中的数据
            $this->_formatParamsForLog($this->paramsLog);
            $paramsJsonLog = json_encode($this->paramsLog, JSON_UNESCAPED_UNICODE);
            $url = $this->getConfig('common', 'GATEWAY_URL');
            PaymentApi::log("Supervision Request {$key}. url:{$url}, params:{$paramsJsonLog}");

            $requestStart = microtime(true);
            $response = Curl::post($url, $requestParams, array(), self::$timeOut);
            $requestCost = round(microtime(true) - $requestStart, 3);
            $code = Curl::$httpCode;
            $error = Curl::$error;
            $responseLog = json_decode($response, true);
            $this->_formatParamsForLog($responseLog);
            PaymentApi::log("Supervision Response {$key}. cost:{$requestCost}s, code:{$code}, error:{$error}, ret:".strip_tags(json_encode($responseLog, JSON_UNESCAPED_UNICODE)));

            if (!empty($response))
            {
                break;
            }
        }

        // 检查请求时，是否有报错
        if (!empty($error) || $code != 200)
        {
            PaymentApi::log("Supervision Resquest failed. Response Or HttpCode Is Error,key:{$key}, cost:{$requestCost}s, code:{$code}, error:{$error}", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'Supervision_Request_Failed', "url:{$url}, code:{$code}, error:{$error}, params:{$paramsJsonLog}, response:{$response}");
            return array();
        }

        // 解密
        $response = json_decode($response, true);
        $result = $this->getData($response);
        $resultArrayLog = $result;
        // 脱敏记录到日志中的数据，[用户绑定银行卡信息查询]接口返回了cardNo银行卡号字段
        if (isset($resultArrayLog['bankCards']) && !empty($resultArrayLog['bankCards']))
        {
            $this->_formatParamsForLog($resultArrayLog['bankCards']);
        }

        if (empty($result))
        {
            PaymentApi::log("Supervision Resquest failed. key:{$key}, cost:{$requestCost}s, code:{$code}, error:{$error}", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'Supervision_Request_Failed', "url:{$url}, code:{$code}, error:{$error}, params:{$paramsJsonLog}, response:" . json_encode($response, JSON_UNESCAPED_UNICODE));
            return array();
        }
        return $result;
    }

    // abstract method
    public function requestMobile($key, $params) {}

    /**
     * 获取请求密文中的参数数组
     * @param array $data 请求的参数
     * 
     * @return array
     */
    public function getData($data) {
        if (!isset($data['tm']) || !isset($data['data']) ) {
            throw new \Exception('存管系统返回结果的格式不正确', SupervisionBase::RESPONSE_CODE_FAILURE);
        }
        $MD5Val = $this->rsaDecrypt($data['tm'],'MERCHANT_PRIVKEY', 'private');
        $aesKey = md5($MD5Val);
        $aesKeyBin = $this->aesKeyConvert($aesKey);
        $decryptData = \libs\utils\Aes::decode($data['data'], $aesKeyBin);
        PaymentApi::log(sprintf("Supervision parseData,AesKey:%s,result:%s", $aesKey, $decryptData));
        // 把data转成数组
        $result = !empty($decryptData) ? json_decode($decryptData, true) : [];
        // 远程返回的不是json格式或为空,返回包有问题
        return is_null($result) ? false : $result;
    }

    /**
     * 构造加密请求参数
     * @param array 需要构造的参数
     */
    public function getResponseParams($params) {
        $requestParams = array();
        $requestParams['merchantId'] = $this->getConfig('common', 'MERCHANT_ID');
        $paramsString = $this->getParamsString($params);
        $MD5Val = md5($paramsString);
        $aesKey = md5($MD5Val);
        $aesKeyBin = $this->aesKeyConvert($aesKey);
        $params['signature'] = $this->getSignature($params);
        $requestParams['data'] = \libs\utils\Aes::encode(json_encode($params, JSON_UNESCAPED_UNICODE), $aesKeyBin);
        $requestParams['tm'] = $this->rsaEncrypt($MD5Val, 'SUPERVISION_PUBKEY', 'public');

        return $requestParams;
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

    /**
     * 必填参数校验
     * @param string $key 接口名
     * @param array $params 参数数组
     * @return void
     */
    public function checkParams($key, $params) {
        $config = $this->getConfig($key);
        if (empty($config)) {
            throw new \Exception(ErrCode::getMsg('ERR_CONFIG'), ErrCode::getCode('ERR_CONFIG'));
        }
        $this->_checkParams($config, $params);
    }

    private function _checkParams($config, $params) {
        // 必填签参数校验
        if (isset($config['requiredFields'])) {
            $requiredFields = $config['requiredFields'];
            $fieldNames = array_keys($requiredFields);
            foreach ($fieldNames as $fieldName) {
                if (!array_key_exists($fieldName, $params) || $params[$fieldName] === '') {
                    $fieldDesc = $requiredFields[$fieldName];
                    PaymentApi::log(sprintf('Supervision checkParams, service:%s, params:%s, %s(%s) Is Required', $config['service'], json_encode($params, JSON_UNESCAPED_UNICODE), $fieldDesc, $fieldName));
                    throw new WXException('ERR_PARAM');
                }
            }
        }
    }

    public function getParams($config, $params)
    {
        // 补全默认值
        if (!empty($config['default'])) {
            $params = array_merge($config['default'], $params);
        }

        // 必填签参数校验
        $this->_checkParams($config, $params);

        //拼接参数
        if (empty($params['callbackUrl']) && isset($config['callbackUrl']))
        {
            $params['callbackUrl'] = $config['callbackUrl'];
        } else if (!empty($params['callbackUrl']) && !empty($config['domain'])) { // 支持业务回调参数
            $params['callbackUrl'] = $config['domain'] . $params['callbackUrl'];
        }
        // 系统配置可能替换业务参数
        if (empty($params['returnUrl']) && isset($config['returnUrl']))
        {
            $params['returnUrl'] = $config['returnUrl'];
        }

        // 补全基本参数
        $params['merchantId'] = $this->getConfig('common', 'MERCHANT_ID');
        $params['method'] = $this->getConfig('common', 'METHOD');
        $params['source'] = $this->getConfig('common', 'SOURCE');
        $params['version'] = isset($config['version']) ? $config['version'] : $this->getConfig('common', 'VERSION');
        $params['reqSn'] = strtoupper(md5(microtime(true) . uniqid() . mt_rand(100000, 999999)));
        $params['requestTime'] = date('Y-m-d H:i:s');
        // 补全配置参数
        $params['service'] = $config['service'];
        // 移动端类型
        if (strpos($config['service'], 'h5') !== false) {
            $params['mobileType'] = isset($params['mobileType']) && in_array($params['mobileType'], [11, 12, 21, 22]) ? (int)$params['mobileType'] : 11;// 请求来源(11：APP 的IOS，12：APP的Android，21：wap的IOS，22：wap的Android)
            $params['source'] = 2; // 请求来源(1:PC|2:MOBILE)
        }
        // 生成签名
        $params['signature'] = $this->getSignature($params);
        // 记录加密前的参数数组
        $this->paramsLog = $params;
        //PaymentApi::log('Supervision request params:' . json_encode($params, JSON_UNESCAPED_UNICODE));
        // 生成requestParams
        $requestParams['merchantId'] = $params['merchantId'];
        $paramsString = $this->getParamsString($params);
        $MD5Val = md5($paramsString);
        // 生成AESKEY
        $aesKey = md5($MD5Val);
        $aesKeyBin = $this->aesKeyConvert($aesKey);
        $requestParams['data'] = \libs\utils\Aes::encode(json_encode($params, JSON_UNESCAPED_UNICODE), $aesKeyBin);
        $requestParams['tm'] = $this->rsaEncrypt($MD5Val, 'SUPERVISION_PUBKEY', 'public');

        return $requestParams;
    }

    /**
     * 获取待签名的原数据
     * @param array $params
     * @return string
     */
    public function getParamsString($params) {
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
        $paramsString = implode('&', $paramsJoin);
        return $paramsString;
    }


    private function _getKey($keyName, $keyType = 'public') {
        $keyContent = $this->getConfig('common', $keyName);
        if (empty($keyContent)) {
            return null;
        }
        $keyContent = chunk_split($keyContent, 40, "\n");
        $keyResource ='';
        if ($keyType == 'public') {
            $keyContent =
"-----BEGIN PUBLIC KEY-----
{$keyContent}-----END PUBLIC KEY-----";
            $keyResource = openssl_pkey_get_public($keyContent);
        }else if ($keyType == 'private') {
            $keyContent =
"-----BEGIN RSA PRIVATE KEY-----
{$keyContent}-----END RSA PRIVATE KEY-----";
            $keyResource = openssl_pkey_get_private($keyContent);
        }
        return $keyResource;
    }

    /**
     * 使用openssl库进行rsa加密
     * @param string $dataString 待加密数据
     */
    private function _rsaEncrpt($dataString,$keyName,$keyType = 'public') {
        if (!is_string($dataString)) {
            return null;
        }
        $encryptDataString = '';
        if ($keyType == 'private') {
            $result = openssl_private_encrypt($dataString, $encryptDataString, $this->_getKey($keyName, $keyType));
        } else if ($keyType == 'public') {
            $result = openssl_public_encrypt($dataString, $encryptDataString, $this->_getKey($keyName, $keyType));
        }
        if (!$result) {
            return null;
        }
        return base64_encode($encryptDataString);
    }

    /**
     * 利用openssl 库进行解密操作
     */
    private function _rsaDecrypt($dataString,$keyName,$keyType = 'public') {
        if (!is_string($dataString)) {
            return null;
        }
        $decryptString = '';
        if ($keyType == 'private') {
            $result = openssl_private_decrypt(base64_decode($dataString), $decryptString, $this->_getKey($keyName, $keyType));
        } else if ($keyType == 'public') {
            $result = openssl_public_decrypt(base64_decode($dataString), $decryptString, $this->_getKey($keyName, $keyType));
        }
        if (!$result) {
            return null;
        }
        return $decryptString;
    }


    /**
     * rsa加密响应数据并以base64加密返回
     * @param string $dataString
     * @return string
     */
    public function rsaEncrypt($dataString, $keyName = 'MERCHANT_PRIVKEY', $keyType = 'private') {
        return $this->_rsaEncrpt($dataString, $keyName, $keyType);
        //$rsa = new \libs\encrypt\RSA;
        //$rsa->setEncryptionMode(CRYPT_RSA_SIGNATURE_PKCS1);
        //$rsa->loadKey($this->getConfig('common', 'SUPERVISION_PUBKEY'));
        //$encryptDataString = $rsa->encrypt($dataString);
        //return base64_encode($encryptDataString);
    }

    /**
     * 解密 使用rsa加密的公钥的base64数据
     * @param string
     * @return string
     */
    public function rsaDecrypt($encryptDataString, $keyName = 'SUPERVISION_PUBKEY', $keyType = 'public') {
        return $this->_rsaDecrypt($encryptDataString, $keyName, $keyType);
        //$rsa = new \libs\encrypt\RSA;
        //$rsa->setEncryptionMode(CRYPT_RSA_SIGNATURE_PKCS1);
        //$rsa->loadKey($this->getConfig('common', 'MERCHANT_PRIVKEY'));
        //$encryptDataString = base64_decode($encryptDataString);
        //$decryptString = $rsa->decrypt($encryptDataString);
        //return $decryptString;
    }

    /**
     * 理财系统调用存管系统时，计算signature
     * @param array $params
     * @return string
     * @see \libs\payment\IPayment::getSignature()
     */
    public function getSignature($params)
    {
        $paramsString = $this->getParamsString($params);
        $md5Val = md5($paramsString);
        $signature = $this->rsaEncrypt($md5Val);
        return $signature;
    }

    /**
     * 存管系统调用理财系统时，计算signature
     * @param array $params
     */
    public function getServiceSignature($params)
    {
        // 生成签名串
        $paramsString = $this->getParamsString($params);
        $md5Val = md5($paramsString);
        // 计算signature
        $rsa = new \libs\encrypt\RSA;
        $rsa->setEncryptionMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $rsa->loadKey($this->getConfig('common', 'MERCHANT_PUBKEY'));
        $encryptDataString = $rsa->encrypt($md5Val);
        return base64_encode($encryptDataString);
    }

    /**
     * 获取待签名数据
     */
    public function getSignatureData($params)
    {
        if (isset($params['signature']))
        {
            unset($params['signature']);
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
        $signatureData = $this->getSignatureData($params);
        //$rsa = new RSA();
        //$rsa->setEncryptionMode(CRYPT_RSA_SIGNATURE_PKCS1);
        //$rsa->loadKey($this->getConfig('common', 'MERCHANT_PRIVKEY'));
        //$md5Val = $rsa->decrypt(base64_decode($signature));
        $md5Val = $this->rsaDecrypt($signature);
        $paramsMD5Value = md5($signatureData);
        $result = $paramsMD5Value === $md5Val;
        if (!$result) {
            PaymentApi::log('Supervision Signature failed. get md5val = '.$md5Val . ', local md5Val = ' . $paramsMD5Value);
            \libs\utils\Alarm::push('supervision', 'SignatureFailure', "Signature failed. params:".json_encode($params, JSON_UNESCAPED_UNICODE));
        }
        return $result;
    }

    /**
     * 存管系统调用理财系统时，验证signature(Gateway Service 访客签名验签)
     * @param array $params 待签名请求数据
     * @param string $signature 签名
     * @return boolean 验签结果
     */
    public function verifyServiceSignature($params, $signature) {
        // 生成签名串
        $signatureData = $this->getSignatureData($params);
        $paramsMD5Value = md5($signatureData);
        // 解密signature
        $rsa = new RSA();
        $rsa->setEncryptionMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $rsa->loadKey($this->getConfig('common', 'MERCHANT_PRIVKEY'));
        $md5Val = $rsa->rsaDecrypt(base64_decode($signature));
        $result = $paramsMD5Value === $md5Val;
        if (!$result) {
            PaymentApi::log('Supervision get md5val = '.$md5Val . ', local md5Val = ' . $paramsMD5Value);
            \libs\utils\Alarm::push('supervision', 'ServiceSignatureFailure', "Signature failed. params:".json_encode($params, JSON_UNESCAPED_UNICODE));
        }
        return $result;
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

        $url = $this->getConfig('common', 'GATEWAY_URL');
        $queryString = http_build_query($params);
        $url .= '?'.$queryString;
        PaymentApi::log("Supervision getRequestUrl {$key}. url:{$url}, params:".json_encode($this->paramsLog, JSON_UNESCAPED_UNICODE));
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

        $url = $this->getConfig('common','GATEWAY_URL');
        PaymentApi::log("Supervision getForm {$key}. url:{$url}, params:".json_encode($this->paramsLog, JSON_UNESCAPED_UNICODE));

        $target = $targetNew ? "target='blank'" : '';

        $html = "<form action='$url' id='$formId' $target style='display:none;' method='post'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";

        return $html;
    }

    /*
     * 存管解密方法
     */
    public function decode($encodedString)
    {
        $data = Aes::decode($encodedString, $this->aesKeyConvert($this->getConfig('common', 'AES_KEY')));
        $data = json_decode($data, true);
        return $data;
    }

    /**
     * 回复加密数据
     * @param string $errCode
     * @param string $errMsg
     * @return string
     */
    public function responseFailure($errKey)
    {
        $supervisionBaseService = new SupervisionBase();
        $errorData = $supervisionBaseService->responseFailure(ErrCode::getCode($errKey), ErrCode::getMsg($errKey));
        return $this->response($errorData);
    }

    /**
     * 回复加密数据
     * @param string $data
     * @return string
     */
    public function response($params)
    {
        $responseData = $this->getResponseParams($params);
        $data = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        PaymentApi::log(sprintf('Supervision response. params:%s', json_encode($params, JSON_UNESCAPED_UNICODE)));
        return $data;
    }

    /**
     * AES Key转换
     */
    public function aesKeyConvert($key)
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
        if (is_array($params))
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
        isset($params['certNo']) && !empty($params['certNo']) && $params['certNo'] = idnoFormat($params['certNo']);
        isset($params['agentPersonNo']) && !empty($params['agentPersonNo']) && $params['agentPersonNo'] = idnoFormat($params['agentPersonNo']);
        isset($params['coperationCard']) && !empty($params['coperationCard']) && $params['coperationCard'] = idnoFormat($params['coperationCard']);
        // 手机号
        isset($params['phone']) && !empty($params['phone']) && $params['phone'] = format_mobile($params['phone']);
        isset($params['mobile']) && !empty($params['mobile']) && $params['mobile'] = format_mobile($params['mobile']);
        isset($params['newPhone']) && !empty($params['newPhone']) && $params['newPhone'] = format_mobile($params['newPhone']);
        isset($params['phoneNo']) && !empty($params['phoneNo']) && $params['phoneNo'] = format_mobile($params['phoneNo']);
        isset($params['agentPersonPhone']) && !empty($params['agentPersonPhone']) && $params['agentPersonPhone'] = format_mobile($params['agentPersonPhone']);
        isset($params['coperationCell']) && !empty($params['coperationCell']) && $params['coperationCell'] = format_mobile($params['coperationCell']);
    }

    /**
     * 存管服务降级
     */
    public static function isServiceDown()
    {
        if((int)app_conf('SUPERVISION_SERVICE_DOWN_SWITCH') === 1) {
            return true;
        }
        return false;
    }

    /**
     * 存管服务降级 实时
     */
    public static function isServiceDownRt()
    {
        $switch = \core\dao\ConfModel::instance()->get('SUPERVISION_SERVICE_DOWN_SWITCH');
        if (isset($switch['value']) && (int) $switch['value'] === 1) {
            return true;
        }
        return false;
    }

    /**
     * 存管服务降级提示信息
     */
    public static function maintainMessage()
    {
        return app_conf('SUPERVISION_SERVICE_MAINTAINCE_MESSAGE')?:'海口联合农商银行系统维护中，请稍后再试';
    }

}
