<?php
namespace libs\utils;

/**
 * 参数签名类
 */
class Signature
{

    /**
     * 生成签名
     * @param array $params
     * @return string
     */
    public static function generate(array $params, $salt)
    {
        ksort($params);

        $paramsJoin = array();
        foreach ($params as $key => $value)
        {
            $paramsJoin[] = "{$key}={$value}";
        }

        $paramsString = implode('&', $paramsJoin).$salt;

        return md5($paramsString);
    }

    /**
     * 验证签名
     * @param array $params
     * @param string $salt
     * @param string $key
     * @return string
     */
    public static function verify(array $params, $salt, $key = 'sign')
    {
        if (!isset($params[$key]))
        {
            return false;
        }

        $signatureParam = strtolower($params[$key]);
        unset($params[$key]);

        $signatureGenerate = self::generate($params, $salt);

        return $signatureParam === $signatureGenerate ? true : false;
    }

}
