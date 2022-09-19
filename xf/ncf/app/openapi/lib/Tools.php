<?php namespace openapi\lib;


use \libs\utils\Aes;
/**
 * 工具类
 */
class Tools
{
    /*
     * 过滤
     */
    static public function dataFilter($array = array(), $filter = array(), $type = 'include')
    {
        if (!($array && is_array($array) && $filter && is_array($filter))) {
            return $array;
        }
        $filter_data = array_shift($filter);
        switch ($type) {
            case 'include':
                if (!$filter_data) {
                    break;
                }
                array_filter(array_keys($array), function ($item) use ($filter_data, &$array) {
                    if (!in_array($item, $filter_data)) {
                        unset($array[$item]);
                    }
                });
                break;
            case 'exclude':
                if (!$filter_data) {
                    break;
                }
                array_filter(array_keys($array), function ($item) use ($filter_data, &$array) {
                    if (in_array($item, $filter_data)) {
                        unset($array[$item]);
                    }
                });
                break;
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::dataFilter($value, $filter, $type);
            }
        }

        return $array;

    }


    static public $iv = 'ebe0234ba12c3e78';
    static public $key = 'a9ec8e76ebe0234ba12c3e788e787114';
    public static function encrypt($data)
    {
        $en_data = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, self::$key, $data, MCRYPT_MODE_CBC, self::$iv));
        $en_data = substr($en_data, 0, -1);
        $result = str_replace(array('/', '+', '='), array('@', '#', '_'), $en_data);
        return $result;
    }

    public static function decrypt($data)
    {
        $data .= '=';
        $en_data = base64_decode(str_replace(array('@', '#', '_'), array('/', '+', '='), $data));
        $result = rtrim((mcrypt_decrypt(MCRYPT_RIJNDAEL_128, self::$key, $en_data, MCRYPT_MODE_CBC, self::$iv)),"\0");
        return $result;
    }


    /**
     * 获取openId，请与开放平台oapi的方法genOpenIdOfOpenapi()算法保持一样一样
     * @param $userID
     * @return string
     */
    public static function getOpenID($userID)
    {
        return self::encryptID($userID);
    }

    public static function getUserIdByOpenID($openID)
    {
        return self::decryptID($openID);
    }

    /**
     * 为id加密
     */
    public static function encryptID($data, $key = 'ebe0234ba12c3e78') {
        $en_data = Aes::encode(strval($data), $key);
        return bin2hex($en_data);
    }
    /**
     * decode id
     */
    public static function decryptID($data, $key = 'ebe0234ba12c3e78') {
        $result = Aes::decode(hex2bin($data), $key);
        return $result;
    }


}
