<?php
/**
 * Created by PhpStorm.
 * User: liaoyebin
 * Date: 2017/12/22
 * Time: 13:25
 */

namespace libs\face;

use libs\utils\Logger;
use libs\utils\Curl;
use libs\utils\Monitor;
use libs\utils\Alarm;
use libs\utils\Aes;

class Yitu
{

    const YITU_CHECK_URL = '/face/basic/check_image_package';

    const YITU_OCR_URL = '/face/basic/ocr';

    const REQUEST_TIMEOUT = 10;

    /**
     * 手机SDK捕获图片数据包验证接口
     * @param $yitu_package 依图大礼包
     */
    public static function checkImage($yitu_package)
    {
        if (empty($yitu_package)) {
            Logger::info("Yitu checkImage. package is empty");
            return false;
        }

        $params = [
            'query_image_package' => $yitu_package,
            'query_image_package_check_same_person' => true,
        ];

        $body = json_encode($params);

        $start = microtime(true);
        $yitu_host = app_conf('YITU_API_HOST');
        $result = Curl::post_json($yitu_host.self::YITU_CHECK_URL, $body, self::REQUEST_TIMEOUT);

        $cost = round(microtime(true) - $start, 3);
        Logger::info("YituResponse. cost:{$cost}, error:". Curl::$error .", code:" . Curl::$httpCode);

        $result = json_decode($result, true);
        if (empty($result)) {
            Logger::info("YituResponse. reuslt empty");
            Monitor::add("YITU_REQUEST_FAIL");
            $msg = "httpcode:" . Curl::$httpCode . ", error:" . Curl::$error;
            Alarm::push('Yitu_Check_Image', '依图接口异常', $msg);
            return false;
        }

        if (!isset($result['rtn']) || $result['rtn'] < 0) {
            Logger::info("YituResponse. reuslt is error, rtn:{$result['rtn']}");
            Monitor::add("YITU_REQUEST_FAIL");
            $msg = "rtn:" . $result['rtn'] . ", message:" . $result['message'];
            Alarm::push('Yitu_Check_Image', '依图接口异常', $msg);
            return false;
        }

        Monitor::add("YITU_REQUEST_SUCC");
        return $result;
    }

    public static function ocr($params)
    {
        $start = microtime(true);
        $yitu_host = app_conf('YITU_API_HOST');
        $result = Curl::post_json($yitu_host.self::YITU_OCR_URL, $params, self::REQUEST_TIMEOUT);

        $cost = round(microtime(true) - $start, 3);
        Logger::info("YituResponse. cost:{$cost}, error:". Curl::$error .", code:" . Curl::$httpCode);

        $result = json_decode($result, true);
        if (empty($result)) {
            Logger::info("YituResponse. reuslt empty");
            Monitor::add("YITU_REQUEST_FAIL");
            $msg = "httpcode:" . Curl::$httpCode . ", error:" . Curl::$error;
            Alarm::push('Yitu_OCR', '依图OCR接口异常', $msg);
            return false;
        }

        if (!isset($result['rtn'])) {
            Logger::info("YituResponse. reuslt is error, rtn:{$result['rtn']}");
            Monitor::add("YITU_REQUEST_FAIL");
            $msg = "rtn:" . $result['rtn'] . ", message:" . $result['message'];
            Alarm::push('Yitu_OCR', '依图OCR接口异常', $msg);
            return false;
        }

        Monitor::add("YITU_REQUEST_SUCC");
        return $result;
    }

}
