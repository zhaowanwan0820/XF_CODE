<?php
/**
 * BUC接口对接
 */
namespace libs\buc;

use libs\utils\Logger;
use libs\utils\Curl;
use libs\utils\Monitor;
use libs\utils\Alarm;
use NCFGroup\Common\Library\RsaLib;

class Buc
{

    const MERCHANT_ID = 'CANDY';

    const WITHDRAW_URI = '/outter-api/v1/merchant/withdraw';

    const WITHDRAW_REAL_NAME_URI = '/outter-api/v1/merchant/real-name-withdraw';

    const LATEST_MARKET_URI = '/outter-api/v1/merchant/get-latest-market';

    const REQUEST_TIMEOUT = 10;

    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 实名提币接口
     * @param $outTradeId
     * @param $toAddr
     * @param $amount
     */
    public  function realNameWithdraw($outTradeId, $toAddr, $amount, $idnoSign)
    {
        $data = [
            'merchantId' => self::MERCHANT_ID,
            'outTradeId' => $outTradeId,
            'toAddr' => $toAddr,
            'amount' => $amount,
            'currency' => "BUC",
            'sendTime' => time(),
            'idnoSign' => $idnoSign,
        ];

        return $this->request(json_encode($data), self::WITHDRAW_REAL_NAME_URI);
    }

    /**
     * 提币接口
     * @param $outTradeId
     * @param $toAddr
     * @param $amount
     */
    public  function withdraw($outTradeId, $toAddr, $amount)
    {
        $data = [
            'merchantId' => self::MERCHANT_ID,
            'outTradeId' => $outTradeId,
            'toAddr' => $toAddr,
            'amount' => $amount,
            'currency' => "BUC",
            'sendTime' => time(),
        ];

        return $this->request(json_encode($data), self::WITHDRAW_URI);
    }


    /**
     * buc行情
     */
    public function buc2usd()
    {
        $data = [
            'merchantId' => self::MERCHANT_ID,
            'symbol' => "BUC/USD",
            'sendTime' => time(),
        ];

        $ret = $this->request(json_encode($data), self::LATEST_MARKET_URI);

        Logger::info("buc2usd request. respCode:{$ret['respCode']}, msg:{$ret['msg']}, price:{$ret['data']['last']}");

        if ($ret['respCode'] == '000000') {
            return $ret['data']['last'];
        }

        //获取行情失败
        return false;
    }

    /**
     * 获取BUC接口host
     */
    public function getBucHost()
    {
        return $GLOBALS['sys_config']['BUC']['HOST'];
    }

    /**
     * 获取商户私钥
     * @return mixed
     */
    public function getMerchantPrivateKey()
    {
        return $GLOBALS['sys_config']['BUC']['MERCHANT_PRIVATE_KEY'];
    }

    /**
     * 获取BITUN公钥
     * @return mixed
     */
    public function getBitunPublicKey()
    {
        return $GLOBALS['sys_config']['BUC']['BITUN_PUBLIC_KEY'];
    }

    /**
     * 使用商户私钥对请求体签名
     * @param $data
     * @return string
     */
    public function merchantSignature($data)
    {
        return RsaLib::Sign($data, $this->getMerchantPrivateKey());
    }

    /**
     * 使用BitUN公钥对响应体验签
     * @param $data
     * @param $sign
     * @return int
     */
    public function bitunVerify($data, $sign)
    {
        return RsaLib::Verify($data, $sign, $this->getBitunPublicKey());
    }

    /**
     * 向BitUN发送请求
     */
    public function request($data, $uri)
    {
        Logger::info('BucOriginalRequest. uri:' . $uri . ', data:' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $sign = $this->merchantSignature($data);
        $header = ['signature: '. $sign];

        $result = Curl::post_json(self::getBucHost() . $uri, $data, self::REQUEST_TIMEOUT, $header, true);

        Logger::info("BucResponse. cost:" .Curl::$cost. ", error:". Curl::$error .", code:" . Curl::$httpCode . ", header:" . str_replace(PHP_EOL, "|", Curl::$responseHeaders) . ", body:" . $result);

        if (Curl::$errno != 0 || empty($result) || Curl::$httpCode != 200) {
            Monitor::add("BUC_REQUEST_FAIL");
            $msg = "httpcode:" . Curl::$httpCode . ", error:" . Curl::$error;
            Alarm::push('BUC_REQUEST', 'BUC接口异常', $msg);
            throw new \Exception('BUC接口请求失败');
        }

        //获取响应头里的signature
        $responseHeaders = explode(PHP_EOL, Curl::$responseHeaders);

        $bitunSign = '';
        foreach ($responseHeaders as $item) {
            if (strstr($item, 'signature:')) {
                $arr = explode('signature', $item);
                $bitunSign = trim($arr[1]);
            }
        }

        if (!$this->bitunVerify($result, $bitunSign)) {
            Monitor::add("BUC_REQUEST_FAIL");
            $msg = "httpcode:" . Curl::$httpCode . ", error:" . Curl::$error . ",signature:". $bitunSign;
            Alarm::push('BUC_REQUEST', 'BUC接口异常', $msg);
            throw new \Exception('BUC接口验签失败');
        }

        return json_decode($result, true);
    }

}
