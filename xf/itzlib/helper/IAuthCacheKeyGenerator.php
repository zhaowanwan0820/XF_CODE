<?php

/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 16/8/22
 * Time: 17:42
 */
class IAuthCacheKeyGenerator
{
    /**
     * @var string redis key prefix.  iauth_provider
     */
    public static $prefix = 'iauth_prov';

    /**
     * api_app_hash 表缓存 hash
     *
     * hashKey
     *   app_key1: json_data1
     *   app_key2: json_data2
     *   app_key3: json_data3
     * @return string
     */
    public static function api_app_hash()
    {
        return self::$prefix . ':app_key_H';
    }

    /**
     * API Nonce 值
     * @param $nonce
     * @return string
     */
    public static function api_nonce($nonce)
    {
        return self::$prefix . ":api_nonce:{$_SERVER['CONSUMER']['KEY']}_{$nonce}";
    }

    /**
     * @return string
     */
    public static function api_rate()
    {
        return self::$prefix . ":api_rate_limit:{$_SERVER['CONSUMER']['KEY']}";
    }

    /**
     * 双因子认证码 cache hash
     *
     * api_dual_factor_H_APP_KEY
     *    identifier1: data1
     *    identifier2: data2
     *    identifier1: data3
     * @return string
     */
    public static function dual_factor_sms_hash()
    {
        return self::$prefix . ":api_dual_factor_H_{$_SERVER['CONSUMER']['KEY']}";
    }

    /**
     * 用户权限列表
     *
     * user_auth_list_UID
     *    CONSUMER_ID: json_data1
     *    CONSUMER_ID: json_data2
     *    CONSUMER_ID: json_data3
     *
     * @param $uid
     * @return string
     */
    public static function user_auth_list_hash($uid)
    {
        return self::$prefix . ":user_auth_list_H_{$uid}";
    }

    /**
     * 权限信息
     * auth_item_H
     *   CODE:CONSUMER_ID: data1
     *   CODE:CONSUMER_ID: data2
     *   CODE:CONSUMER_ID: data3
     * @return string
     */
    public static function auth_item_hash()
    {
        return self::$prefix . ":auth_item_H";
    }
}
