<?php

namespace NCFGroup\Common\Library;

/**
 * 参数签名类
 */
class SignatureLib
{

    /**
     * 生成签名
     * @param array $params
     * @return string
     */
    public static function generate(array $params, $salt, $urlencode = false)
    {
        ksort($params);

        $paramsJoin = array();
        foreach ($params as $key => $value)
        {
            $paramsJoin[] = "{$key}={$value}";
        }

        $paramsString = implode('&', $paramsJoin).$salt;

        if($urlencode === true) {
            return md5(urlencode($paramsString));
        } else {
            return md5($paramsString);
        }
    }

    /**
     * 验证签名
     * @param array $params
     * @param string $salt
     * @param string $key
     * @return string
     */
    public static function verify(array $params, $salt, $key = 'sign', $urlencode = false)
    {
        if (!isset($params[$key]))
        {
            return false;
        }

        $signatureParam = strtolower($params[$key]);
        unset($params[$key]);

        $signatureGenerate = self::generate($params, $salt, $urlencode);
        return $signatureParam === $signatureGenerate ? true : false;
    }

}
