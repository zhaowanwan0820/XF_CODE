<?php

/**
 * 通用 Header 生成类
 * User: Devon
 * Date: 16/8/19
 * Time: 11:06
 */

namespace itzlib\sdk;

class AuthHeader
{
    public static function isPrivateIP($ip)
    {
        /* return false if it is an invalid ip. */
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return false;
        }
        /* return false if it is a public ip */
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
    }

    public static function get(
        $key,
        $secret,
        $nonce = '',
        $time = 0,
        $verb = 'GET',
        $path = '',
        $params = []
    ) {
        if ($time == 0) {
            $headers['Time'] = time();
        } else {
            $headers['Time'] = $time;
        }

        if ($nonce == '') {
            $headers['Nonce'] = substr(uniqid(), -4);
        } else {
            $headers['Nonce'] = $nonce;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if (self::isPrivateIP($ip)) {
            $sign = '';
        } else {
            $sign = self::sign($secret, $headers['Nonce'], $headers['Time'], $verb, $path, $params);
        }
        $headers['Authentication'] = base64_encode("{$key}:{$sign}");

        return $headers;
    }

    public static function sign($secret, $nonce, $time, $verb, $path, $params)
    {
        $part_a = $nonce . $time . $verb . $path;
        $part_b = "";
        if (!empty($params)) {
            ksort($params, SORT_REGULAR);
            foreach ($params as $key => $param) {
                $part_b .= $key . $param;
            }
        }

        $str = $part_a . $part_b;
        return hash_hmac('sha256', $str, $secret, true);
    }

}
