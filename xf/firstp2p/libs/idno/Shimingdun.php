<?php
/**
 * 实名盾通道已经废弃
 */

namespace libs\idno;

use libs\utils\Logger;
use libs\utils\Curl;
use libs\utils\Monitor;
use libs\utils\Alarm;

class Shimingdun {
    
    const SMD_ACCOUNT = 'bjwxyf';
    
    const SMD_KEY = 'bjwxyf';

    const SMD_TOKEN = 'cae573c9eb7ee2f0d79d598711dbcffb';
    
    const SMD_AUTH_URL = 'http://111.161.74.125/api/service/auth';

    const SMD_COMPARE_URL = 'http://111.161.74.125/api/service/photo/compare';

    const REQUEST_TIMEOUT = 10;

    /**
     * 实名认证
     * @param $name
     * @param $idno
     */
    public static function verify($name, $idno) {
        $params = [
            'id_card' => $idno,
            'uname' => $name
        ];

        $headers = [
            'Authorization: Basic ' . base64_encode(self::SMD_ACCOUNT.':'.self::SMD_KEY),
            'X-API-KEY: ' . self::SMD_TOKEN
        ];

        $start = microtime(true);
        $result = Curl::post(self::SMD_AUTH_URL, $params, $headers, self::REQUEST_TIMEOUT);

        $cost = round(microtime(true) - $start, 3);
        Logger::info("ShimingdunResponse. cost:{$cost}, error:". Curl::$error .", code:" . Curl::$httpCode . ", result:{$result}");

        $result = json_decode($result, true);
        if (!isset($result['status_code'])) {
            return array('code' => '-110', 'msg' => '请求失败');
        }

        if ($result['status_code'] == 100) {
            return array('code' => '0', 'msg' => '身份证号码匹配');
        }

        if ($result['status_code'] == 101) {
            return array('code' => '-200', 'msg' => '身份证号码不匹配');
        }

        if ($result['status_code'] == 102) {
            return array('code' => '-300', 'msg' => '身份证号码错误');
        }

        if ($result['status_code'] == 103 || $result['status_code'] == 400) {
            return array('code' => '-111', 'msg' => '数据格式错误');
        }

        return array('code' => '-90', 'msg' => "返回错误码code:{$result['status_code']}");
    }

    /**
     * 照片比对
     * @param $idno
     * @param $img
     */
    public static function compare($idno, $img)
    {
        $params = [
            'id_card' => $idno,
            'img' => $img
        ];

        $headers = [
            'Authorization: Basic ' . base64_encode(self::SMD_ACCOUNT.':'.self::SMD_KEY),
            'X-API-KEY: ' . self::SMD_TOKEN
        ];

        $start = microtime(true);
        $result = Curl::post(self::SMD_COMPARE_URL, $params, $headers, self::REQUEST_TIMEOUT);

        $cost = round(microtime(true) - $start, 3);
        Logger::info("ShimingdunResponse. cost:{$cost}, error:". Curl::$error .", code:" . Curl::$httpCode . ", result:{$result}");
        $result = json_decode($result, true);

        if (empty($result)) {
            Monitor::add('SHIMINGDUN_COMPARE_ERROR');
            $msg = "httpcode:" . Curl::$httpCode . ", error:" . Curl::$error;
            Alarm::push('Shimingdun_Compare', '实名盾接口异常', $msg);
            return false;
        }
        if ($result['status_code'] == 100) {
            Monitor::add('SHIMINGDUN_COMPARE_SUCC');
            return $result;
        }
        if ($result['status_code'] == 101) {
            Monitor::add('SHIMINGDUN_COMPARE_FAIL');
            return $result;
        }

        return $result;
    }

}
