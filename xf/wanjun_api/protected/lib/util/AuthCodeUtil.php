<?php

class AuthCodeUtil{

    /**
     * @param $str
     * @param string $business
     * @param int $expire_time
     * @return bool|string
     */
    public static function makeCode($str, $business = 'AL',$expire_time=300)
    {
        $code = md5(FunctionUtil::getRequestNo($business).'-'.$str);
        $setCode = RedisService::getInstance()->set($code, $str, $expire_time);
        if ($setCode) {
            return $code;
        }
        return false;
    }

    /**
     * @param $code
     * @return mixed
     */
    public static function getCodeInfo($code)
    {
        $str = RedisService::getInstance()->get($code);
        RedisService::getInstance()->del($code);
        return $str;

    }
}