<?php
/**
 * 榕树身份认证
 */
namespace libs\idno;

use libs\utils\Logger;
use libs\utils\Curl;
use libs\utils\Monitor;
use libs\utils\Alarm;

class Rongshu
{

    const APP_ID = 'ucfgroup';

    const APP_KEY = '49b42947ad4a3241de504dac21ffa4ea';

    //const URL_VERIFY = 'http://api.rongshuio.com/idcheck';
    const URL_VERIFY = 'http://rongapi.cn/idcheck';

    const URL_PHOTO_CHK = 'http://rongapi.cn/photochk';

    //榕树身份核查（返照）接口
    const URL_VERIFY_PHOTO = 'http://rongapi.cn/idphoto';

    const REQUEST_TIMEOUT = 10;

    /**
     * 身份认证
     */
    public static function verify($name, $idno)
    {
        $result = self::request($name, $idno, self::URL_VERIFY);

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

    /**
     * 身份认证（返照）
     */
    public  function verifyPhoto($name, $idno)
    {
        $result = self::request($name, $idno, self::URL_VERIFY_PHOTO);

        if (!isset($result['ResultCode'])) {
            return array('code' => '-110', 'msg' => '请求失败');
        }

        if ($result['ResultCode'] === '1000') {
            return array('code' => '0', 'msg' => '身份证号码匹配', 'Identifier' => ['Photo' => $result['Photo']]);
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

    /**
     * 请求榕树
     */
    private static function request($name, $idno, $url)
    {
        $params = array(
            'Name' => $name,
            'IdCode' => $idno,
        );
        ksort($params);
        $params['Sign'] = md5(implode('', $params).self::APP_KEY);
        $params['Appid'] = self::APP_ID;

        $start = microtime(true);
        $result = Curl::post($url, $params, [], self::REQUEST_TIMEOUT);

        $cost = round(microtime(true) - $start, 3);
        Logger::info("RongshuResponse. cost:{$cost}, error:". Curl::$error .", code:" . Curl::$httpCode . ", result:{$result}");

        $result = json_decode($result, true);

        return $result;
    }

    public static function compare($name, $idno, $img)
    {
        $params = array(
            'Name' => $name,
            'IdCode' => $idno,
            'Photo' => $img,
        );
        ksort($params);
        $params['Sign'] = md5(implode('', $params) . self::APP_KEY);
        $params['Appid'] = self::APP_ID;

        $start = microtime(true);
        $result = Curl::post(self::URL_PHOTO_CHK, $params, [], self::REQUEST_TIMEOUT);

        $cost = round(microtime(true) - $start, 3);
        Logger::info("RongshuResponse compare. cost:{$cost}, name:{$name}, error:" . Curl::$error . ", code:" . Curl::$httpCode . ", result:{$result}");

        if (empty($result)) {
            Monitor::add('RONGSHU_COMPARE_ERROR');
            $msg = "httpcode:" . Curl::$httpCode . ", error:" . Curl::$error;
            Alarm::push('Rongshu_Compare', '榕树比对接口异常', $msg);
            return false;
        }

        $result = json_decode($result, true);

        //1000比对成功（计费），1001姓名和身份证号不⼀致（计费），1002姓名和身份证号匹配，库⽆照⽚（计费）
        if ($result['ResultCode'] == 1000 || $result['ResultCode'] == 1001 || $result['ResultCode'] == 1002) {
            Monitor::add('RONGSHU_COMPARE_SUCC');
        } else {
            Monitor::add('RONGSHU_COMPARE_FAIL');
        }

        return $result;
    }
}
