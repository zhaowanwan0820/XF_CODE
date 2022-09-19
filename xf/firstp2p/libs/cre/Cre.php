<?php
/**
 * CRE接口对接
 */
namespace libs\cre;

use libs\utils\Logger;
use libs\utils\Curl;
use libs\utils\Monitor;
use libs\utils\Alarm;
use libs\utils\Signature;

class Cre
{
    //⽤户是否绑定并有没有兑换的资格uri
    const VALIDATE_URI = '/cre/user/validate';

    //cre兑换接口
    const EXCHANGE_URI = '/cre/xb/exchange';

    //取消兑换接口
    const REFUND_URI = '/cre/xb/refund';

    //cre注册地址
    const REGISTER_URI = '/#/register';

    // 用户未注册
    const RESPONSE_CODE_NO_REGISTER = 10002;

    const REQUEST_TIMEOUT = 5;

    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 获取CRE接口host
     */
    public function getCreHost()
    {
        return $GLOBALS['sys_config']['CRE']['HOST'];
    }

    /**
     * 获取key
     * @return mixed
     */
    public function getCreSignKey()
    {
        return $GLOBALS['sys_config']['CRE']['SIGN_KEY'];
    }

    /**
     * 查看⽤户是否绑定并有没有兑换的资格
     * @param $userId
     */
    public function requestUserValidate($userId)
    {
        $url = $this->getCreHost() . self::VALIDATE_URI . '?userid=' .$userId;
        Logger::info('CreOriginalRequest. url:' . $url);

        $result = Curl::get($url);
        Logger::info("CreResponse. cost:" .Curl::$cost. ", error:". Curl::$error .", code:" . Curl::$httpCode . ", result:" . $result);

        if (Curl::$errno != 0 || empty($result) || Curl::$httpCode != 200) {
            Monitor::add("CRE_REQUEST_FAIL");
            $msg = "httpcode:" . Curl::$httpCode . ", error:" . Curl::$error;
            Alarm::push('CRE_REQUEST', 'CRE接口异常', $msg);
            throw new \Exception('CRE接口请求失败');
        }

        return json_decode($result, true);
    }

    /**
     * 兑换cre
     * @param $params
     * @return mixed
     */
    public function requestExchange($userId, $creAmount, $candyAmout, $txId)
    {
        $params['userid'] = $userId;
        $params['cre_value'] = $creAmount;
        $params['xb_value'] = $candyAmout;
        $params['tx_id'] = $txId;
        return $this->request($params, self::EXCHANGE_URI);
    }

    /**
     * 取消兑换
     * @param $params
     * @return array|bool|\mix|mixed|\stdClass|string
     * @throws \Exception
     */
    public function requestRefund($txId)
    {
        $params['tx_id'] = $txId;
        return $this->request($params, self::REFUND_URI);
    }

    /**
     * 拼接cre注册地址
     * @param $userId
     * @param $backUrl
     * @return string
     */
    public function getCreRegisterUrl($userId, $backUrl)
    {
        $params['userid'] = $userId;
        $params['back_url'] = $backUrl;
        $params['sign'] = strtoupper(Signature::generate($params, $this->getCreSignKey()));

        return $this->getCreHost() . self::REGISTER_URI . '?' . http_build_query($params);
    }

    /**
     * 向cre发送请求
     */
    private function request($data, $uri)
    {
        $data['sign'] = strtoupper(Signature::generate($data, $this->getCreSignKey()));
        Logger::info('CreOriginalRequest. uri:' . $uri . ', data:' . json_encode($data, JSON_UNESCAPED_UNICODE));

        $result = Curl::post(self::getCreHost() . $uri, $data, [], self::REQUEST_TIMEOUT);

        Logger::info("CreResponse. cost:" .Curl::$cost. ", error:". Curl::$error .", code:" . Curl::$httpCode . ", result:" . $result);

        if (Curl::$errno != 0 || empty($result) || Curl::$httpCode != 200) {
            Monitor::add("CRE_REQUEST_FAIL");
            $msg = "httpcode:" . Curl::$httpCode . ", error:" . Curl::$error;
            Alarm::push('CRE_REQUEST', 'CRE接口异常', $msg);
            throw new \Exception('CRE接口请求失败');
        }

        return json_decode($result, true);
    }
}
