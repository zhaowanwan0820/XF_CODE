<?php

namespace NCFGroup\Common\Library\Face\Providers;

use NCFGroup\Common\Library\CommonLogger as Logger;
use NCFGroup\Common\Library\Curl;
use NCFGroup\Common\Library\Alarm;

/**
 * 依图活体检测
 */
class Yitu
{

    const YITU_CHECK_URL = '/face/basic/check_image_package';

    /**
     * 手机SDK捕获图片数据包验证接口
     * @param $packageData 活体数据
     */
    public static function livenessDetect($packageData)
    {
        if (empty($packageData)) {
            Logger::info("yitu checkImage. package is empty");
            return false;
        }

        $params = [
            'query_image_package' => $packageData,
            'query_image_package_check_same_person' => true,
        ];

        $curl = Curl::instance();
        $config = getDi()->getConfig()->yitu->toArray();
        $resultString = $curl->post($config['host'] . self::YITU_CHECK_URL, json_encode($params));
        Logger::info("yitu response. result:{$resultString}, cost:{$curl->resultInfo['cost']}, error:{$curl->resultInfo['error']}, code:{$curl->resultInfo['code']}");

        $result = json_decode($resultString, true);
        if (!isset($result['rtn']) || $result['rtn'] < 0) {
            Logger::info("YituResponse. reuslt is error, rtn:{$result['rtn']}");
            Alarm::push('face', 'yitu response  error', "result:{$resultString}, cost:{$curl->resultInfo['cost']}, error:{$curl->resultInfo['error']}, code:{$curl->resultInfo['code']}");
            return false;
        }

        return $result;
    }

}
