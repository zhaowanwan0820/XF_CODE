<?php
/**
 * User: duxuefeng
 * Date: 2018/7/14
 * Time: 18:43
 */

namespace openapi\conf\adddealconf\common;

class CommonConf {
    //平台与client_id map
    static $_ALLOW_PLATFORM_CLIENT_ID = array(
        'online'=> array(
            'retail' => '882962c4ca8d8678d9380a1d',
        ),
        'dev'=> array(
            'retail' => '74ba4171a4217265537f4d1b',
        ),
        'producttest'=> array(
            'retail' => '74ba4171a4217265537f4d1b',
        ),
        'test'=> array(
            'retail' => '74ba4171a4217265537f4d1b',
        ),

    );

    public static function getAllowPlatformClientId($client_id) {
        if (empty($client_id)) {
            return false;
        }
        $env = app_conf('ENV_FLAG');
        $platform = array_search($client_id,self::$_ALLOW_PLATFORM_CLIENT_ID[$env]);
        return $platform;
    }

}
