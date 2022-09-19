<?php

namespace NCFGroup\Common\Library\Idno\Providers;

use NCFGroup\Common\Library\CommonLogger;
use NCFGroup\Common\Library\Curl;

/**
 * 榕树实名认证
 */
class RongShu
{

    const APP_ID = 'ucfgroup';

    const APP_KEY = '49b42947ad4a3241de504dac21ffa4ea';

    //const URL_VERIFY = 'http://api.rongshuio.com/idcheck';
    const URL_VERIFY = 'http://rongapi.cn/idcheck';

    const URL_PHOTO_CHK = 'http://rongapi.cn/photochk';

    //榕树身份核查（返照）接口
    const URL_VERIFY_PHOTO = 'http://rongapi.cn/idphoto';

    /**
     * 身份认证
     */
    public static function verifyName($name, $idno)
    {
        $result = self::request($name, $idno, self::URL_VERIFY);

        if (!isset($result['ResultCode'])) {
            return array('code' => '-110', 'msg' => '请求失败');
        }

        if ($result['ResultCode'] === '1000') {
            return array('code' => '0', 'msg' => '身份证号码匹配');
        }

        return self::formatErrorResult($result);
    }

    /**
     * 身份认证（返照）
     */
    public static function verifyNameReturnPhoto($name, $idno)
    {
        $result = self::request($name, $idno, self::URL_VERIFY_PHOTO);

        if (!isset($result['ResultCode'])) {
            return array('code' => '-110', 'msg' => '请求失败');
        }

        if ($result['ResultCode'] === '1000') {
            return array('code' => '0', 'msg' => '身份证号码匹配', 'Identifier' => ['Photo' => $result['Photo']]);
        }

        return self::formatErrorResult($result);
    }

    /**
     * 错误返回
     */
    private static function formatErrorResult($result)
    {
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

        $curl = Curl::instance();
        $result = $curl->post($url, $params);

        CommonLogger::info("RongshuResponse. cost:" . $curl->resultInfo['cost'] . ", error:" . $curl->resultInfo['error'] . ", errno:" . $curl->resultInfo['errno'] . ", code" . $curl->resultInfo['code'] . ", result:{$result}");

        $result = json_decode($result, true);

        if (empty($result)) {
            CommonLogger::info("RongshuResponse. result empty");
            return false;
        }

        return $result;
    }

    /**
     * 人脸识别
     */
    public static function verifyPhoto($name, $idno, $img)
    {
        $params = array(
            'Name' => $name,
            'IdCode' => $idno,
            'Photo' => $img,
        );

        ksort($params);
        $params['Sign'] = md5(implode('', $params) . self::APP_KEY);
        $params['Appid'] = self::APP_ID;

        $curl = Curl::instance();
        $result = $curl->post(self::URL_PHOTO_CHK, $params);

        CommonLogger::info("RongshuResponse. cost:" . $curl->resultInfo['cost'] . ", error:" . $curl->resultInfo['error'] . ", errno:" . $curl->resultInfo['errno'] . ", code" . $curl->resultInfo['code'] . ", result:{$result}");

        $result = json_decode($result, true);

        if (empty($result)) {
            CommonLogger::info("RongshuResponse. result empty");
            return false;
        }

        return $result;
    }
}
