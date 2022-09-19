<?php
/**
 * 先锋支付网关接口
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */
namespace libs\utils;

use libs\utils\Curl;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Signature;

class PaymentGatewayApi
{

    const REQUEST_API_RETRY_COUNT = 3;

    private $config = null;

    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->config = include APP_ROOT_PATH.'conf/paymentgatewayapi.conf.php';
    }

    /**
     * 接口请求
     */
    public function request($key, $params = array())
    {
        //发起请求，记录请求时间
        $retryCount = empty($this->config['API_LIST'][$key]['retry']) ? 1 : self::REQUEST_API_RETRY_COUNT;
        if(isset($this->config['API_LIST'][$key]['retry'])) {
            unset($this->config['API_LIST'][$key]['retry']);
        }
        for ($i = 0 ; $i < $retryCount; ++$i) {
            if ($i > 0)
            {
                PaymentApi::log("Request retry. key:$key, times:$i, cost:{$cost}s, code:$code, error:$error", Logger::ERR);
            }
            $params['secId'] = $this->config['SEC_ID'];
            $params['token'] = $this->applyToken();
            $params['merchantId'] = $this->config['MERCHANT_ID'];
            $params += $this->config['API_LIST'][$key];
            $params['sign'] = Signature::generate($params, $this->config['SIGNATURE_SALT']);
            $paramsJson = json_encode($params, JSON_UNESCAPED_UNICODE);

            PaymentApi::log("Request $key. gateway:{$this->config['GATEWAY']}, params:$paramsJson");

            //发起请求，记录请求时间
            $start = microtime(true);
            $response = Curl::post($this->config['GATEWAY'], $params);
            $cost = round(microtime(true) - $start, 3);
            $code = Curl::$httpCode;
            $error = Curl::$error;
            PaymentApi::log("Response $key. cost:{$cost}s, code:$code, error:$error, ret:{$response}");
            if(!empty($response)) {
                break;
            }
        }
        //解密
        $resultArray = json_decode($response, true);
        if (empty($resultArray)) {
            PaymentApi::log("ResquestFailed. key:$key, cost:{$cost}s, code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentgatewayapi', 'Request_Failed', "code:$code, error:$error, params:$paramsJson, response:$response");
            throw new \Exception('Request failed');
        }

        return $resultArray;
    }

    private function applyToken()
    {

        $params = array();
        $params['service'] = 'REQ_GET_TOKEN';
        $params['reqId'] = time();
        $params['merchantId'] = $this->config['MERCHANT_ID'];
        $params['version'] = $this->config['VERSION'];
        $params['secId'] = $this->config['SEC_ID'];
        $params['sign'] = Signature::generate($params, $this->config['SIGNATURE_SALT']);

        //请求, 失败重试3次
        for ($i = 0; $i < 3; $i++) {
            if ($i > 0) {
                PaymentApi::log("Apply token retry. times:$i, cost:{$cost}s, code:$code, error:$error", Logger::ERR);
            }

            $start = microtime(true);
            $response = Curl::post($this->config['GATEWAY'], $params);
            $cost = round(microtime(true) - $start, 3);
            $code = Curl::$httpCode;
            $error = Curl::$error;
            PaymentApi::log("Apply token response. gateway:{$this->config['GATEWAY']}, cost:{$cost}s, code:$code, error:$error, params:".json_encode($params).", response:$response");

            if (!empty($response)) {
                break;
            }
        }

        //处理结果
        $resultArray = json_decode($response, true);
        $token = isset($resultArray['result']) ? $resultArray['result'] : '';
        if (empty($token)) {
            PaymentApi::log("Apply token failed. code:$code, error:$error", Logger::ERR);
            \libs\utils\Alarm::push('paymentgatewayapi', 'ApplyToken_failed', "code:$code, error:$error, params:".json_encode($params).", response:$response");
            throw new \Exception('Apply token failed');
        }

        return $token;
    }


    public function getRequestUrl($key, $params = array()) {
        $params['secId'] = $this->config['SEC_ID'];
        $params['token'] = $this->applyToken();
        $params['merchantId'] = $this->config['MERCHANT_ID'];
        $params += $this->config['API_LIST'][$key];
        $params['sign'] = Signature::generate($params, $this->config['SIGNATURE_SALT']);
        $url = $this->config['GATEWAY'];
        PaymentApi::log("Ucfpay getRequestUrl $key. url:$url, params:".json_encode($params));
        $queryString = http_build_query($params);
        $url .= '?'.$queryString;
        PaymentApi::log("Ucfpay getRequestUrl:$url");
        return $url;
    }

    /**
     * 获取前台可提交的表单
     */
    public function getForm($key, $params = array(), $formId = 'pay_form', $targetNew = true)
    {
        $params['secId'] = $this->config['SEC_ID'];
        $params['token'] = $this->applyToken();
        $params['merchantId'] = $this->config['MERCHANT_ID'];
        $params += $this->config['API_LIST'][$key];
        $params['sign'] = Signature::generate($params, $this->config['SIGNATURE_SALT']);

        PaymentApi::log("getForm $key. gateway:{$this->config['GATEWAY']}, params:".json_encode($params));

        $target = $targetNew ? "target='blank'" : '';
        $html = "<form action='{$this->config['GATEWAY']}' id='$formId' $target style='display:none;' method='post'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";

        return $html;
    }

    /**
     * 回调接口回复数据
     */
    public function response($data)
    {
        if (!is_string($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        PaymentApi::log("Callback response. ret:{$data}");
        return $data;
    }

    /**
     * 签名验证
     */
    public function verify($params)
    {
        return Signature::verify($params, $this->config['SIGNATURE_SALT'], 'sign');
    }

}
