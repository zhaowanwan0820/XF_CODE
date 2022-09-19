<?php
/**
 * 榕树身份认证
 */
namespace libs\idno;

use libs\utils\Logger;
use libs\utils\Curl;

class Rongshu
{

    const APP_ID = 'ucfgroup';

    const APP_KEY = '49b42947ad4a3241de504dac21ffa4ea';

    //const URL_VERIFY = 'http://api.rongshuio.com/idcheck';
    const URL_VERIFY = 'http://rongapi.cn/idcheck';

    const REQUEST_TIMEOUT = 3;

    /**
     * 身份认证
     */
    public static function verify($name, $idno)
    {
        $params = array(
            'Name' => $name,
            'IdCode' => $idno,
        );
        ksort($params);
        $params['Sign'] = md5(implode('', $params).self::APP_KEY);
        $params['Appid'] = self::APP_ID;

        $start = microtime(true);
        $result = Curl::post(self::URL_VERIFY, $params, [], self::REQUEST_TIMEOUT);

        $cost = round(microtime(true) - $start, 3);
        Logger::info("RongshuResponse. cost:{$cost}, error:". Curl::$error .", code:" . Curl::$httpCode . ", result:{$result}");

        $result = json_decode($result, true);
        if (!isset($result['ResultCode'])) {
            return array('code' => '-110', 'msg' => '请求失败');
        }

        if ($result['ResultCode'] === '1000') {
            return array('code' => '0', 'msg' => '身份证号码匹配');
        }

        if ($result['ResultCode'] === '1001') {
            return array('code' => '-200', 'msg' => '身份证号码不匹配');
        }

        if ($result['ResultCode'] === '1002') {
            return array('code' => '-300', 'msg' => '身份证号码错误');
        }

        if ($result['ResultCode'] === '2003' || $result['ResultCode'] === '2004' || $result['ResultCode'] === '2005') {
            return array('code' => '-111', 'msg' => '数据格式错误');
        }

        return array('code' => '-90', 'msg' => "返回错误码code:{$result['ResultCode']}");
    }

    public function verifyPhoto($name, $idno)
    {
        return array('code' => '-404', 'msg' => '暂无接入图像接口');
    }

}
