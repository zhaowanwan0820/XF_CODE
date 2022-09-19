<?php

namespace NCFGroup\Common\Library\Risk;

class RiskUtils
{
    const DEVICE_WEB = 0;
    const DEVICE_WAP = 8;
    const DEVICE_IOS = 3;
    const DEVICE_UNKNOWN = 5;
    const DEVICE_ANDROID = 4;

    /**
     * 获取第三方所需指纹参数
     */
    public static function getFinger()
    {
        $fingerPrint = '';
        if (isset($_COOKIE["FRMS_FINGERPRINT"])) {
            $fingerPrint =  $_COOKIE["FRMS_FINGERPRINT"];
        } else if (isset($_SERVER['HTTP_FINGERPRINT'])) {
            $fingerPrint =  $_SERVER['HTTP_FINGERPRINT'];
        }

        //风控升级后web设备指纹更改为取cookie的BSFIT_DEVICEID字段,即设备指纹外码
        if (isset($_COOKIE["BSFIT_DEVICEID"])) {
            $fingerPrint = $_COOKIE["BSFIT_DEVICEID"];
        }

        return $fingerPrint;
    }

    /**
     * 获取毫秒时间戳作为请求时间
     */
    public static function getMillisecond() {
        return intval(microtime(true) * 1000);
    }

    /**
     * 获取设备来源
     */
    public static function getDevice()
    {
        if (APP == self::DEVICE_WEB) {
            return self::DEVICE_WEB;
        }

        $data = $_SERVER['HTTP_USER_AGENT'];

        if (stripos($data, 'Android') !== false) {
            return self::DEVICE_ANDROID;
        }

        if (stripos($data, 'iOS') !== false) {
            return self::DEVICE_IOS;
        }

        if (stripos($data, 'WAP') !== false) {
            return self::DEVICE_WAP;
        }

        return self::DEVICE_UNKNOWN;
    }
}
