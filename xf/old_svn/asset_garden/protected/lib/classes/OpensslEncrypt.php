<?php

/**
 * | @author 李鹏博^life <907215837@qq.com>
 * +----------------------------------------------------------------------
 * | @copyright 2016 - 2019 HQ
 * +----------------------------------------------------------------------
 * | @version $Id: 2019/4/22 16:33 jiahe_new OpensslEncrypt.php 李鹏博^life $
 * +----------------------------------------------------------------------
 */


class  OpensslEncrypt
{
    const IV  = "d89fb057f6d4f03g";//加密向量，16个字节
    const KEY = 'e9c8e878ee8e2658';//密钥，16个字节


    /**
     * 加密字符串
     * @param string $strContent 待加密的字符串内容
     * @param string $key 加密key
     * @return string 返回加密后的字符串，失败返回false
     */
    public static function encrypt($strContent, $key = self::KEY, $iv = self::IV)
    {
        $strEncrypted = openssl_encrypt($strContent, "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($strEncrypted);
    }


    /**
     * 解密字符串
     * @param string $strEncryptCode加密后的字符串
     * @param string $key 加密key
     * @return string 返回解密后的字符串，失败返回false
     */
    public static function decrypt($strEncryptCode, $key = self::KEY, $iv = self::IV)
    {
        $strEncrypted = base64_decode($strEncryptCode);

        return openssl_decrypt($strEncrypted, "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv);
    }


    /**
     * 加密用户信息
     * @param $info
     * @return string
     */
    public static function encryptionMemberInfo($info)
    {
        $info['time'] = time();
        $json_info    = json_encode($info);

        return self::encrypt($json_info);
    }


    /**
     * 解密用户信息
     * @param $decrypt
     * @return mixed
     */
    public static function decryptMemberInfo($decrypt)
    {
        $json_info = self::decrypt($decrypt);

        return json_decode($json_info, true);
    }


    /**
     * 校验密钥
     * @param $ciphertext 密文
     * @param array $plaintext 需要校验的明文 ，可以为空
     * @return bool
     */
    public static function checkCiphertext($ciphertext, $plaintext = [])
    {
        $info = self::decryptMemberInfo($ciphertext);

        //密钥有效期为一天，超时则无效
        if (empty($info) || (time() - 86400) > $info['time']) {
            return false;
        }
        //如果明文为空，无需校验
        if (empty($plaintext)) {
            return $info;
        }
        foreach ($plaintext as $k => $v) {
            //如果明文跟密文不一致，则不合法
            if ($info[$k] != $v) {
                return false;
            }
        }

        return $info;
    }
}