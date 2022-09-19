<?php
namespace libs\payment\yeepay;

use libs\payment\IPayment;
use libs\encrypt\Rijndael;
use libs\encrypt\AES;
use libs\encrypt\DES;
use libs\encrypt\Hash;
use libs\encrypt\RSA;
use libs\encrypt\TripleDES;
use libs\math\BigInteger;

use libs\utils\PaymentApi;
use libs\utils\Curl;
use libs\utils\Logger;

class Yeepay implements IPayment
{
    // CURL 参数
    public $httpInfo;
    public $httpHeader = array();
    public $httpCode;
    public $useragent = 'Yeepay MobilePay PHPSDK v1.1x';
    public $connecttimeout = 30;
    public $timeout = 30;
    public $sslVerifypeer = FALSE;
    // Yeepay 参数
    private $merchantAccount;
    private $merchartPublicKey;
    private $merchantPrivateKey;
    private $yeepayPublicKey;
    private $bindBankcardURL;
    private $confirmBindBankcardURL;
    private $directBindPayURL;
    private $paymentQueryURL;
    private $paymentConfirmURL;
    private $withdrawURL;
    private $queryWithdrawURL;
    private $queryAuthbindListURL;
    private $bankCardCheckURL;
    private $payClearDataURL;
    private $refundURL;
    private $refundQueryURL;
    private $refundClearDataURL;
    // 加密
    private $RSA;
    private $AES;
    private $AESKey;

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

    /**
     * 支付相关配置
     * @var array
     */
    private static $config;

    public function __construct() {
        // 加密类
        $this->RSA = new RSA();
        $this->RSA->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $this->RSA->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->AES = new AES(CRYPT_AES_MODE_ECB);
    }

    /**
     * 获取商户编号
     * @return type
     */
    public function getMerchartAccount() {
        return $this->merchantAccount;
    }

    /**
     * 获取商户私匙
     * @return type
     */
    public function getMerchantPrivateKey() {
        return $this->merchantPrivateKey;
    }

    /**
     * 获取商户AESKey
     * @return type
     */
    public function getmerchantAESKey() {
        return $this->random(16, 1);
    }

    /**
     * 获取易宝公匙
     * @return type
     */
    public function getYeepayPublicKey() {
        return $this->yeepayPublicKey;
    }

    /**
     * 格式化字符串
     * @param type $text
     * @return type
     */
    public function formatString($text) {
        return $text == '' || empty($text) || is_null($text) ? '' : trim($text);
    }

    /**
     * String2Integer
     * @param type $text
     * @return type
     */
    public function string2Int($text) {
        return $text == '' || empty($text) || is_nan($text) ? 0 : intval($text);
    }

    /**
     * 生成随机字符串
     * @param type $length 字符串长度
     * @param type $numeric 数字模式
     * @return type string
     */
    public function random($length, $numeric = 0) {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        $hash = '';
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }

    /**
     * 绑卡请求接口请求地址
     * @return type
     */
    public function getBindBankcardURL() {
        return $this->bindBankcardURL;
    }

    /**
     * 绑卡确认接口请求地址
     * @return type
     */
    public function getConfirmBindBankcardURL() {
        return $this->confirmBindBankcardURL;
    }

    /**
     * 支付接口请求地址
     * @return type
     */
    public function getDirectBindPayURL() {
        return $this->directBindPayURL;
    }

    /**
     * 订单查询请求地址
     * @return type
     */
    public function getPaymentQueryURL() {
        return $this->paymentQueryURL;
    }

    /**
     * 确定支付请求地址
     * @return type
     */
    public function getpaymentConfirmURL() {
        return $this->paymentConfirmURL;
    }

    /**
     * 取现接口请求地址
     * @return type
     */
    public function getWithdrawURL() {
        return $this->withdrawURL;
    }

    /**
     * 取现查询请求地址
     * @return type
     */
    public function getQueryWithdrawURL() {
        return $this->queryWithdrawURL;
    }

    /**
     * 取现查询请求地址
     * @return type
     */
    public function getQueryAuthbindListURL() {
        return $this->queryAuthbindListURL;
    }

    /**
     * 银行卡信息查询请求地址
     * @return type
     */
    public function getBankCardCheckURL() {
        return $this->bankCardCheckURL;
    }

    /**
     * 支付清算文件下载请求地址
     * @return type
     */
    public function getPayClearDataURL() {
        return $this->payClearDataURL;
    }

    /**
     * 单笔退款请求地址
     * @return type
     */
    public function getRefundURL() {
        return $this->refundURL;
    }

    /**
     * 退款查询请求地址
     * @return type
     */
    public function getRefundQueryURL() {
        return $this->refundQueryURL;
    }

    /**
     * 退款清算文件请求地址
     * @return type
     */
    public function getRefundClearDataURL() {
        return $this->refundClearDataURL;
    }

    /**
     * 绑定银行卡
     * @param type $identityid
     * @param type $identitytype
     * @param type $requestid
     * @param type $cardno
     * @param type $idcardno
     * @param type $username
     * @param type $phone
     * @param type $advicesmstype
     * @param type $registerphone
     * @param type $registerdate
     * @param type $registerip
     * @param type $registeridcardno
     * @param type $registercontact
     * @param type $os
     * @param type $imei
     * @param type $userip
     * @param type $ua
     * @return type
     */
    public function bindBankcard($identityid, $identitytype, $requestid, $cardno, $idcardno, $username, $phone, $advicesmstype,$registerphone, $registerdate, $registerip, $registeridcardno, $registercontact, $os, $imei, $userip, $ua) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'identityid' => $identityid,
            'identitytype' => $identitytype,
            'requestid' => $requestid,
            'cardno' => $cardno,
            'idcardtype' => '01',
            'idcardno' => $idcardno,
            'username' => $username,
            'phone' => $phone,
            'advicesmstype' => $advicesmstype,
            'registerphone' => $registerphone,
            'registerdate' => $registerdate,
            'registerip' => $registerip,
            'registeridcardtype' => '01',
            'registeridcardno' => $registeridcardno,
            'registercontact' => $registercontact,
            'os' => $os,
            'imei' => $imei,
            'userip' => $userip,
            'ua' => $ua
        );
        return $this->post($this->getBindBankcardURL(), $query);
    }

    /**
     * 确定绑卡
     * @param type $requestid
     * @param type $validatecode
     * @return type
     */
    public function bindBankcardConfirm($requestid, $validatecode) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'requestid' => $requestid,
            'validatecode' => $validatecode
        );
        return $this->post($this->getConfirmBindBankcardURL(), $query);
    }

    /**
     * 获取绑卡记录
     * @param type $identityid
     * @param type $identitytype
     * @return type
     */
    public function bankcardList($identityid, $identitytype) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'identityid' => $identityid,
            'identitytype' => $identitytype
        );
        return $this->get($this->getQueryAuthbindListURL(), $query);
    }

    /**
     * 获取绑卡支付请求
     * @param type $orderid
     * @param type $transtime
     * @param type $amount
     * @param type $productname
     * @param type $productdesc
     * @param type $identityid
     * @param type $identitytype
     * @param type $card_top
     * @param type $card_last
     * @param type $orderexpdate
     * @param type $callbackurl
     * @param type $imei
     * @param type $userip
     * @param type $ua
     * @return type
     */
    public function directPayment($orderid, $transtime, $amount, $productname, $productdesc, $identityid, $identitytype, $card_top, $card_last, $orderexpdate, $callbackurl, $imei, $userip, $ua) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'orderid' => $orderid,
            'transtime' => $transtime,
            'currency' => 156,
            'amount' => $amount,
            'productname' => $productname,
            'productdesc' => $productdesc,
            'identityid' => $identityid,
            'identitytype' => $identitytype,
            'card_top' => $card_top,
            'card_last' => $card_last,
            'orderexpdate' => $orderexpdate,
            'callbackurl' => $callbackurl,
            'imei' => $imei,
            'userip' => $userip,
            'ua' => $ua
        );
        return $this->post($this->getDirectBindPayURL(), $query);
    }

    /**
     * 确认支付
     * @param type $orderid
     * @param type $validatecode
     * @return type
     */
    public function confirmPayment($orderid, $validatecode = '') {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'orderid' => $orderid,
            'validatecode' => $validatecode
        );
        return $this->post($this->getpaymentConfirmURL(), $query);
    }

    /**
     * 交易记录查询
     * @param type $orderid
     * @param type $yborderid
     * @return type
     */
    public function paymentQuery($orderid, $yborderid) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'orderid' => $orderid,
            'yborderid' => $yborderid
        );
        return $this->get($this->getPaymentQueryURL(), $query);
    }

    /**
     * 提现
     * @param type $requestid
     * @param type $identityid
     * @param type $identitytype
     * @param type $card_top
     * @param type $card_last
     * @param type $amount
     * @param type $imei
     * @param type $userip
     * @param type $ua
     * @return type
     */
    public function withdraw($requestid, $identityid, $identitytype, $card_top, $card_last, $amount, $imei, $userip, $ua) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'requestid' => $requestid,
            'identityid' => $identityid,
            'identitytype' => $identitytype,
            'card_top' => $card_top,
            'card_last' => $card_last,
            'amount' => $amount,
            'currency' => 156,
            'drawtype' => 'NATRALDAY_NORMAL',
            'imei' => $imei,
            'userip' => $userip,
            'ua' => $ua
        );
        return $this->post($this->getWithdrawURL(), $query);
    }

    /**
     * 银行卡信息查询
     * @param type $cardno
     * @return type
     */
    public function bankcardCheck($cardno) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'cardno' => $cardno
        );
        return $this->post($this->getBankCardCheckURL(), $query);
    }

    /**
     * 提现查询
     * @param type $requestid
     * @param type $ybdrawflowid
     * @return type
     */
    public function withdrawQuery($requestid, $ybdrawflowid) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'requestid' => $requestid,
            'ybdrawflowid' => $ybdrawflowid
        );
        return $this->get($this->getQueryWithdrawURL(), $query);
    }

    /**
     * 获取支付清算文件
     * @param type $startdate
     * @param type $enddate
     * @return type
     */
    public function payClearData($startdate, $enddate) {
        $query = array(
            'merchantaccount' => $this->getMerchartAccount(),
            'startdate' => $startdate,
            'enddate' => $enddate
        );
        
        $url = $this->getUrl($this->getPayClearDataURL(), $query);
        $data = $this->http($url, 'GET');
        if ($this->httpInfo['http_code'] == 405) {
            throw new \Exception('此接口不支持使用GET方法请求', 1003);
        }
        return $data;
    }

    /**
     * 
     * @param string $url
     * @param type $query
     * @return string
     */
    public function getUrl($url, $query) {
        $request = $this->buildRequest($query);
        $url .= '?' . http_build_query($request);
        return $url;
    }

    public function buildRequest(&$query) {
        if (!array_key_exists('merchantno', $query)) {
            $query['merchantno'] = $this->getMerchartAccount();
        }
        $sign = $this->RSASign($query);
        $query['sign'] = $sign;
        $request = array();
        $request['merchantno'] = $this->getMerchartAccount();
        $request['encryptkey'] = $this->getEncryptkey();
        $request['data'] = $this->AESEncryptRequest($query);
        return $request;
    }

    /**
     * 用RSA 签名请求
     * @param array $query
     * @return string
     */
    protected function RSASign(array $query) {
        if (array_key_exists('sign', $query)) {
            unset($query['sign']);
        }
        ksort($query);
        $this->RSA->loadKey($this->getMerchantPrivateKey());
        $sign = base64_encode($this->RSA->sign(join('', $query)));
        return $sign;
    }

    /**
     * 通过RSA，使用易宝公钥，加密本次请求的AESKey
     * @return string
     */
    protected function getEncryptkey() {
        if (!$this->AESKey) {
            $this->generateAESKey();
        }
        $this->RSA->loadKey($this->yeepayPublicKey);
        $encryptKey = base64_encode($this->RSA->encrypt($this->AESKey));
        return $encryptKey;
    }

    /**
     * 生成一个随机的字符串作为AES密钥
     * @param number $length
     * @return string
     */
    protected function generateAESKey($length = 16) {
        $baseString = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $AESKey = '';
        $_len = strlen($baseString);
        for ($i = 1; $i <= $length; $i++) {
            $AESKey .= $baseString[rand(0, $_len - 1)];
        }
        $this->AESKey = $AESKey;
        return $AESKey;
    }

    /**
     * 返回易宝返回数据的AESKey
     * @param unknown $encryptkey
     * @return Ambigous <string, boolean, unknown>
     */
    protected function getYeepayAESKey($encryptkey) {
        $this->RSA->loadKey($this->merchantPrivateKey);
        $yeepayAESKey = $this->RSA->decrypt(base64_decode($encryptkey));
        return $yeepayAESKey;
    }

    /**
     * 通过AES加密请求数据
     * @param array $query
     * @return string
     */
    protected function AESEncryptRequest(array $query) {
        if (!$this->AESKey) {
            $this->generateAESKey();
        }
        $this->AES->setKey($this->AESKey);
        return base64_encode($this->AES->encrypt(json_encode($query)));
    }

    /**
     * 模拟HTTP协议
     * @param string $url
     * @param string $method
     * @param string $postfields
     * @return mixed
     */
    protected function http($url, $method, $postfields = NULL) {
        $this->httpInfo = array();
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->sslVerifypeer);
        curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        curl_setopt($ci, CURLOPT_HEADER, FALSE);
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                if (!empty($postfields)) {
                    $url .= '?' .$postfields;
                }
                break;
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($postfields)) {
                    $url .= '?' .$postfields;
                }
        }
        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        $this->httpCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $this->httpError = curl_error($ci);
        $this->httpErrno = curl_errno($ci);
        $this->httpInfo = array_merge($this->httpInfo, curl_getinfo($ci));
        $this->url = $url;
        curl_close($ci);
        return $response;
    }

    /**
     * Get the header info to store.
     * @param type $ch
     * @param type $header
     * @return type
     */
    public function getHeader($ch, $header) {
        $i = strpos($header, ':');
        if (!empty($i)) {
            $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
            $value = trim(substr($header, $i + 2));
            $this->httpHeader[$key] = $value;
        }
        return strlen($header);
    }

    /**
     * 解析返回数据
     * @param type $data
     * @return type
     * @throws \Exception
     */
    protected function parseReturnData($data) {
        $return = json_decode($data, true);
        if (array_key_exists('error_code', $return) && !array_key_exists('status', $return)) {
            throw new \Exception($return['error_msg'], $return['error_code']);
        }
        return $this->parseReturn($return['data'], $return['encryptkey']);
    }

    /**
     * 解析返回数据
     * @param type $data
     * @param type $encryptkey
     * @return type
     * @throws \Exception
     */
    protected function parseReturn($data, $encryptkey) {
        $AESKey = $this->getYeepayAESKey($encryptkey);
        $return = $this->AESDecryptData($data, $AESKey);
        $return = json_decode($return, true);
        if (!array_key_exists('sign', $return)) {
            if (array_key_exists('error_code', $return)) {
                throw new \Exception($return['error_msg'], $return['error_code']);
            }
            throw new \Exception('请求返回异常', 1001);
        } else {
            if (!$this->RSAVerify($return, $return['sign'])) {
                throw new \Exception('请求返回签名验证失败', 1002);
            }
        }
        if (array_key_exists('error_code', $return) && !array_key_exists('status', $return)) {
            throw new \Exception($return['error_msg'], $return['error_code']);
        }
        unset($return['sign']);
        return $return;
    }

    /**
     * 通过AES解密易宝返回的数据
     * @param string $data
     * @param string $AESKey
     * @return Ambigous <boolean, string, unknown>
     */
    protected function AESDecryptData($data, $AESKey) {
        $this->AES->setKey($AESKey);
        return $this->AES->decrypt(base64_decode($data));
    }

    /**
     * 使用易宝公钥检测易宝返回数据签名是否正确
     * @param array $query
     * @param string $sign
     * @return boolean
     */
    protected function RSAVerify(array $return, $sign) {
        if (array_key_exists('sign', $return)) {
            unset($return['sign']);
        }
        ksort($return);
        $this->RSA->loadKey($this->yeepayPublicKey);
        foreach ($return as $k => $val) {
            if (is_array($val)) {
                $return[$k] = self::cn_json_encode($val);
            }
        }
        return $this->RSA->verify(join('', $return), base64_decode($sign));
    }

    /**
     * json_encode
     * @param type $value
     * @return type
     */
    public static function cn_json_encode($value) {
        if (defined('JSON_UNESCAPED_UNICODE')) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            $encoded = urldecode(json_encode(self::array_urlencode($value)));
            return preg_replace(array('/\r/', '/\n/'), array('\\r', '\\n'), $encoded);
        }
    }

    /**
     * urlencode
     * @param type $value
     * @return type
     */
    public static function array_urlencode($value) {
        if (is_array($value)) {
            return array_map(array('yeepay', 'array_urlencode'), $value);
        } elseif (is_bool($value) || is_numeric($value)) {
            return $value;
        } else {
            return urlencode(addslashes($value));
        }
    }

    /**
     * 使用POST的方式发出API请求
     * @param type $url
     * @param type $query
     * @return type
     * @throws \Exception
     */
    protected function post($url, $query) {
        //发起请求， 记录请求时间
        $requestStart = microtime(true);
        $request = $this->buildRequest($query);
        $data = $this->http($url, 'POST', http_build_query($request));
        if ($this->httpInfo['http_code'] == 405) {
            throw new \Exception('此接口不支持使用POST方法请求', 1004);
        }
        $requestCost = round(microtime(true) - $requestStart, 3);
        if (empty($data))
        {
            PaymentApi::log("Yeepay Resquest failed. key:$key, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'Yeepay Request_Failed', "url:$url, code:$code, error:$error, params:$paramsJson, response:$data");
            return array();
        }
        return $this->parseReturnData($data);
    }

    /**
     * 使用GET的模式发出API请求
     * @param string $url
     * @param array $query
     * @return array
     */
    protected function get($url, $query) {
        $request = $this->buildRequest($query);
        $url .= '?' . http_build_query($request);
        $data = $this->http($url, 'GET');
        if ($this->httpInfo['http_code'] == 405) {
            throw new \Exception('此接口不支持使用GET方法请求', 1003);
        }
        return $this->parseReturnData($data);
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
        isset($params['cardno']) && !empty($params['cardno']) && $params['cardno'] = formatBankcard($params['cardno']);
        // 身份证号
        isset($params['idcardno']) && !empty($params['idcardno']) && $params['idcardno'] = idnoFormat($params['idcardno']);
        // 手机号
        isset($params['phone']) && !empty($params['phone']) && $params['phone'] = format_mobile($params['phone']);
    }

    /**
     * 解析Json数据
     * @param array $response
     * @return boolean|array
     */
    public function parseJsonData($response)
    {
        if (is_array($response))
        {
            if (empty($response))
            {
                return false;
            }
            $return = $response;
        }else{
            $return = json_decode($response, true);
            // 远程返回的不是 json 格式, 说明返回包有问题
            if (is_null($return))
            {
                return false;
            }
        }

        // 接口返回错误信息
        if (array_key_exists('error_code', $return) && !array_key_exists('status', $return))
        {
            PaymentApi::log("Yeepay Response failed. key:$key, code:$code, error:$error, response:$response", Logger::ERR);
            return $return;
        }
        // 解密
        $AESKey = $this->getYeepayAESKey($return['encryptkey']);
        $result = $this->AESDecryptData($return['data'], $AESKey);
        $resultData = $resultDataLog = json_decode($result, true);
        // 脱敏记录到日志中的数据，[4.8 查询绑卡信息列表]接口返回了phone字段
        if (isset($resultDataLog['cardlist']) && !empty($resultDataLog['cardlist']))
        {
            $this->_formatParamsForLog($resultDataLog['cardlist']);
        }
        $resultJsonLog = json_encode($resultDataLog);
        PaymentApi::log("Yeepay Aes key:{$AESKey} decode. result:{$resultJsonLog}");
        return $resultData;
    }

    public function getConfig($key, $subKey = '')
    {
        if (self::$config === null)
        {
            self::$config = include APP_ROOT_PATH . PaymentApi::CONFIG_FILE;
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
        
    }

    /**
     * 生成openapi的接口签名
     * @param array $config
     * @param array $params
     * @param string $salt
     * @return array
     */
    public function getOpenApiSign($config, $params, $salt = '')
    {
        ksort($params);
        $paramsJoin = array();
        foreach ($params as $key => $value)
        {
            if (!is_null($value)) {
                $paramsJoin[] = $key . $value;
            }
        }
        $paramsString = $salt . implode('', $paramsJoin) . $salt;

        $params['sign'] = strtoupper(md5($paramsString));
        return $params;
    }

    /**
     * 获取可提交的表单
     * @param string $key 接口关键字，参考paymentapi.conf.php
     * @param array $params 参数数组
     * @param string $formId Form的DOM结点ID
     * @param boolen $target 表单是否新窗口打开
     * @return string
     */
    public function getForm($key, $params, $formId = 'yeepay_form', $target = true, $salt = '')
    {
        $config = $this->getConfig($key);
        if (empty($config) || !isset($config['formUrl']) || empty($config['formUrl']))
        {
            return '';
        }

        // Form表单action地址
        $url = $config['formUrl'];
        PaymentApi::log("Yeepay getForm {$key}. url:{$url}, params:" . json_encode($params));

        // 是否在新窗口打开
        $targetHtml = $target ? "target='blank'" : '';

        $html = "<form action='{$url}' id='{$formId}' {$targetHtml} style='display:none;' method='post'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='{$key}' value='{$value}' />\n";
        }
        $html .= "</form>\n";
        return $html;
    }

    /**
     * 计算签名
     * @see \libs\payment\IPayment::getSignature()
     */
    public function getSignature($params)
    {
        return $this->RSASign($params);
    }

    /**
     * 验证签名
     * @see \libs\payment\IPayment::verifySignature()
     */
    public function verifySignature($params, $signature)
    {
        return $this->RSAVerify($params, $signature);
    }

    /**
     * 统一请求易宝接口
     * @see \libs\payment\IPayment::request()
     */
    public function request($key, $params)
    {
        \libs\utils\Monitor::add('PAYMENTAPI_YEEPAY_REQUEST');
        \libs\utils\Monitor::add('PAYMENTAPI_YEEPAY_REQUEST_' . $key);

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

        // 发起请求，记录请求时间
        $retryCount = empty($config['retry']) ? 1 : PaymentApi::REQUEST_API_RETRY_COUNT;
        // 获取HTTP请求方式
        $requestMethod = isset($config['requestMethod']) ? strtoupper($config['requestMethod']) : 'POST';
        // 接口返回数据格式
        $responseFormat = isset($config['responseFormat']) ? strtolower($config['responseFormat']) : 'json';

        for ($i = 0; $i < $retryCount; $i++)
        {
            if ($i > 0)
            {
                \libs\utils\Monitor::add('PAYMENTAPI_YEEPAY_REQUEST_FAILED');
                PaymentApi::log("Yeepay Request retry. key:$key, times:$i, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            }
            // 拼接请求参数
            $request = $this->buildRequest($params);
            // 脱敏记录到日志中的数据
            $this->_formatParamsForLog($params);
            $initParamsJson = json_encode($params, JSON_UNESCAPED_UNICODE);
            $paramsJson = json_encode($request, JSON_UNESCAPED_UNICODE);
            $url = $config['url'];
            PaymentApi::log("Yeepay Request $key. url:$url, requestMethod:$requestMethod,initParamsJson:$initParamsJson,params:$paramsJson");
            $requestStart = microtime(true);
            $response = $this->http($url, $requestMethod, http_build_query($request));
            $requestCost = round(microtime(true) - $requestStart, 3);
            $code = $this->httpCode;
            $error = $this->httpError;
            PaymentApi::log("Yeepay Response $key. cost:{$requestCost}s, code:$code, error:$error, ret:".strip_tags($response));

            if (!empty($response))
            {
                break;
            }
        }

        // 检查请求时，是否有报错
        if (!empty($error) || $code != 200)
        {
            PaymentApi::log("Yeepay Resquest failed. Response Or HttpCode Is Error,key:$key, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'Yeepay_Request_Failed', "url:$url, code:$code, error:$error, params:$paramsJson, response:$response");
            return array(
               'ret' => false,
               'error_code' => self::ERROR_RESPONSE_NETWORK + $this->httpErrno,
               'error_msg' => self::ERROR_NETWORK_MESSAGE,
               'http_code' => $code,
               'result' => array(),
            );
        }

        // 接口返回数据格式
        switch ($responseFormat) {
            case 'string':
                $result = $this->parseJsonData($response);
                $resultData = false === $result ? $response : $result;
                break;
            case 'json':
                $resultData = $this->parseJsonData($response);
                break;
        }

        if (empty($resultData))
        {
            PaymentApi::log("Yeepay Resquest failed. key:{$key}, cost:{$requestCost}s, code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentapi', 'Yeepay_Request_Failed', "url:$url, code:$code, error:$error, params:$paramsJson, response:$response");
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

    }

    /**
     * 回调接口返回数据
     */
    public function response($data)
    {
        if (!is_string($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        PaymentApi::log("Yeepay Callback response. ret:{$data}");
        return $data;
    }

    public function setGlobalConfig($config)
    {
        self::$config = $config;
        // 商户配置
        $this->merchantAccount = $config['common']['MERCHANT_ID'];
        $this->merchartPublicKey = $config['common']['MERCHANT_PUBKEY'];
        $this->merchantPrivateKey = $config['common']['MERCHANT_PRIVKEY'];
        $this->yeepayPublicKey = $config['common']['YEEPAY_PUBKEY'];

        // API URI 配置
        $this->bindBankcardURL = 'https://ok.yeepay.com/payapi/api/tzt/invokebindbankcard';
        $this->confirmBindBankcardURL = 'https://ok.yeepay.com/payapi/api/tzt/confirmbindbankcard';
        $this->directBindPayURL = 'https://ok.yeepay.com/payapi/api/tzt/pay/bind/request';
        $this->paymentQueryURL = 'https://ok.yeepay.com/merchant/query_server/pay_single';
        $this->paymentConfirmURL = 'https://ok.yeepay.com/payapi/api/tzt/pay/confirm/validatecode';
        $this->withdrawURL = 'https://ok.yeepay.com/payapi/api/tzt/withdraw';
        $this->queryWithdrawURL = 'https://ok.yeepay.com/payapi/api/tzt/drawrecord';
        $this->queryAuthbindListURL = 'https://ok.yeepay.com/payapi/api/bankcard/authbind/list';
        $this->bankCardCheckURL = 'https://ok.yeepay.com/payapi/api/bankcard/check';
        $this->payClearDataURL = 'https://ok.yeepay.com/merchant/query_server/pay_clear_data';
        $this->refundURL = '';
        $this->refundQueryURL = '';
        $this->refundClearDataURL = '';
    }

}

