<?php

namespace NCFGroup\Common\Library\encrypt;

/**
 * 底层DB数据加解密服务：对查询数据库的sql语句加密过滤，对返回结果集解密过滤.
 * 需加解密的表及具体字段通过common.conf进行配置
 *
 * @author 吕宝松 <lvbaosong@ucfgroup.com>
 */
class DBDes {
    public static $DES_OFF = false;

    /**
     * 工具类函数，用于手动调用加解密
     * @param string|array $$specifiedFields 指定要解密的字段名
     * @param boolean $encrypt 默认加密 true:加密;false:解密
     * @param array  $data  待加解密的数组，一维(单行数据)或二维(多行数据)
     */
    public static function encryptOrDecryptBySpecifiedFields($specifiedFields, $data, $encrypt = true) {
        if (!$specifiedFields || empty($data)) {
            return $data;
        }

        if (!is_array($specifiedFields)) {
            $specifiedFields = array($specifiedFields);
        }

        foreach($specifiedFields as $fieldName) {
            if (count($data) == count($data, 1)) {
                if (isset($data[$fieldName])){
                    if ($encrypt) {
                        $data[$fieldName] = self::encryptOneValue($data[$fieldName]);
                    } else {
                        $data[$fieldName] = self::decryptOneValue($data[$fieldName]);
                    }
                }
            } else {
                foreach ($data as $index=>$item) {
                    if (isset($item[$fieldName])) {
                        if ($encrypt) {
                            $item[$fieldName] = self::encryptOneValue($item[$fieldName]);
                        } else {
                            $item[$fieldName] = self::decryptOneValue($item[$fieldName]);
                        }

                        $data[$index] = $item;
                    }
                }
            }
        }

        return $data;
    }

    /**
     *  加密一个数值
     * @param string $value  待解密的值
     * @return  string
     */
    public static function encryptOneValue($value){
        if (!empty($value)) {
//            $value =  Aes::encode($value, self::getKey());
            $value = GibberishAES::enc($value, self::getKey());
        }
        return $value;
    }

    /**
     *  解密一个数值
     * @param string $value  待解密的值
     * @return  string|false 解密失败则返回false
     */
    public static function decryptOneValue($value){
        if (!empty($value)) {
//            $value = Aes::decode($value,self::getKey());
            $value = GibberishAES::dec($value, self::getKey());
        }

        return $value;
    }

    /**
     *  工具函数:解密一组加密的数据
     * @param array $data  待解密数组
     * @return array
     */
    public static function decryptMultiValue($data){
        if (empty($data)) {
            return $data;
        }

        foreach($data as $key =>$value){
            $decryptValue = self::decryptOneValue($value);
            if ($decryptValue) {
                $data[$key] = $decryptValue;
            }
        }

        return $data;
    }

    /**
     * 工具函数:加密一组数据
     * @param array $data  待加密数组
     * @return array
     */
    public static function encryptMultiValue($data) {
        if (empty($data)) {
            return $data;
        }

        foreach ($data as $key =>$value) {
            $encryptValue = self::encryptOneValue($value);
            if ($encryptValue) {
                $data[$key] = $encryptValue;
            }
        }

        return $data;
    }

    private static function getKey(){
        $key = get_cfg_var('p2p_db_des_key');
        if(!isset($key) || empty($key)){
            throw new \Exception("db des key is null");
        }
        return $key;
    }
}
