<?php

class ActionLogUtil{
    //业务类型
    private static $business_type=[
        'shop_info_edit',//商城信息管理 编辑
        'shop_info_auth',//商城信息管理 审核
        'shop_user_allow_list_edit',//商城用户白名单 编辑
        'shop_user_allow_list_auth',//商城用户白名单 审核
    ];

    private $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public static function encrypt($data,$secret)
    {
        $des3 = new self($secret);
        return base64_encode(openssl_encrypt(json_encode($data), 'DES-ECB', $des3->key, OPENSSL_RAW_DATA));
    }

    public static function decrypt($str,$secret)
    {
        $des3 = new self($secret);
        $str = base64_decode($str);
        return json_decode(openssl_decrypt($str, 'DES-ECB', $des3->key, OPENSSL_RAW_DATA),true);
    }
}