<?php

namespace NCFGroup\Common\Library\Status;

use NCFGroup\Common\Library\CommonLogger as Logger;
use NCFGroup\Common\Library\Curl;


/**
 * 状态推送
 */
class Status
{

    const SET_STATUS_URL = '/status/set';

    const GET_STATUS_URL = '/status/get';

    /**
     * 设置状态
     */
    public static function set($key, $value)
    {
        $params = [
            'key' => $key,
            'value' => $value,
        ];

        $config = getDi()->getConfig()->status->backend->toArray();
        shuffle($config);
        $url = $config[0];

        $curl = Curl::instance();
        $result = $curl->post($url . self::SET_STATUS_URL, $params);

        Logger::info("status set. key:{$key}, value:{$value}, result:{$result}, cost:{$curl->resultInfo['cost']}, error:{$curl->resultInfo['error']}, errno:{$curl->resultInfo['errno']}, code:{$curl->resultInfo['code']}");

        return json_decode($result, true);
    }

    /**
     * 获取前端请求的URL
     */
    public static function getUrl()
    {
        $config = getDI()->getConfig()->status->frontend->toArray();
        return $config[0].self::GET_STATUS_URL;
    }

}