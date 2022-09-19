<?php
/**
 * Rsa 加解密类
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace NCFGroup\Common\Library;

class RsaLib
{
    //商户私钥签名
    public static function Sign($originalData, $key)
    {
        $signData='';
        $pem = chunk_split($key, 64, "\n");//转换为pem格式的私钥（PKCS8格式）
        $pem = "-----BEGIN PRIVATE KEY-----\n".$pem."-----END PRIVATE KEY-----\n";
        if(openssl_sign($originalData, $signData, $pem, OPENSSL_ALGO_SHA256))
        {
            return base64_encode($signData);
        }
        return '';
    }

    //先锋公钥验证签名
    public static function Verify($originalData, $signData, $key)
    {
        $pem = chunk_split($key, 64, "\n");//转换为pem格式的公钥（PKCS8格式）
        $pem = "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----\n";
        $signature= base64_decode($signData);
        $ok = openssl_verify($originalData, $signature, $pem, OPENSSL_ALGO_SHA256);
        return $ok;
    }

    //商户私钥解密
    public static function PrivateDecrypt($originalData, $key)
    {
        $decryptData = '';
        $pem = chunk_split($key, 64, "\n");//转换为pem格式的私钥（PKCS8格式）
        $pem = "-----BEGIN PRIVATE KEY-----\n".$pem."-----END PRIVATE KEY-----\n";
        $originalData= base64_decode($originalData);
        openssl_private_decrypt($originalData, $decryptData, $pem);
        return $decryptData;
    }

    //私钥加密
    public static function PrivateEncrypt($originalData, $key)
    {
        $encryptData = '';
        $pem = chunk_split($key, 64, "\n");//转换为pem格式的私钥（PKCS8格式）
        $pem = "-----BEGIN PRIVATE KEY-----\n".$pem."-----END PRIVATE KEY-----\n";
        openssl_private_encrypt($originalData, $encryptData, $pem);
        return base64_encode($encryptData);
    }

    //公钥加密
    public static function PublicEncrypt($originalData, $key)
    {
        $encryptData = '';
        $pem = chunk_split($key, 64, "\n");//转换为pem格式的公钥（PKCS8格式）
        $pem = "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----\n";
        openssl_public_encrypt($originalData, $encryptData, $pem);
        return base64_encode($encryptData);
    }


    //公钥解密
    public static function PublicDecrypt($originalData, $key)
    {
        $decryptData = '';
        $pem = chunk_split($key, 64, "\n");//转换为pem格式的公钥（PKCS8格式）
        $pem = "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----\n";
        $originalData= base64_decode($originalData);
        openssl_public_decrypt($originalData, $decryptData, $pem);
        return $decryptData;
    }
}
