<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/11
 * Time: 18:43
 */

namespace openapi\conf\adddealconf\common;

class CommonConf {
    static $_RULES = array(
        "relativeSerialno" => array("filter" => "required", "message" => "relativeSerialno is required"), //客户端业务主键
        "realName" => array("filter" => "required", "message" => "realName is required"), //客户名称
        "idno" => array("filter" => "required", "message" => "idno is required"), //身份证号码
        "mobile" => array("filter" => "required", "message" => "mobile is required"), //手机号码
        "financAdd" => array("filter" => "required", "message" => "financAdd is required"), //融资地址
        "borrowAmount" => array("filter" => "required", "message" => "borrowAmount is required"), //提款金额
        "repayPeriod" => array("filter" => "required", "message" => "repayPeriod is required"), ///提款期限
        "bankCard" => array("filter" => "required", "message" => "bankCard is required"), //实际放款账号
        "bankShortName" => array("filter" => "string", 'option' => array('optional' => true)), //实际放款银行简称
        "bankZone" => array("filter" => "string", 'option' => array('optional' => true)), //实际放款账户开户网点
        "cardName" => array("filter" => "required", "message" => "cardName is required"), //实际放款账号开户人名称
        "openID" => array("filter" => "required", "message" => "openID is required"), //网信openID
        "consultFeeRate" => array("filter" => "string", 'option' => array('optional' => true)), //年化借款咨询费率
    );
    static $_ALLOW_CLIENT_ID = array(
        'online'=> array(
            'angli' => 'cbbc3e85de19e34020db8cfc',
            'gfd' => '0f7ed5e6ced827be2a39239b',
            'retail' => '882962c4ca8d8678d9380a1d',
        ),
        'dev'=> array(
            'angli' => 'angliadddealtest',
            'gfd' => 'treefinance',
            'retail' => '74ba4171a4217265537f4d1b',
        ),
        'producttest'=> array(
            'angli' => 'angliadddealtest',
            'gfd' => 'treefinance',
            'retail' => '74ba4171a4217265537f4d1b',
        ),
        'test'=> array(
            'angli' => 'angliadddealtest',
            'gfd' => 'treefinance',
            'retail' => '74ba4171a4217265537f4d1b',
        ),

    );
    //平台与client_id map
    static $_ALLOW_PLATEORM_CLIENT_ID = array(
        'online'=> array(
            'ZZJR' => '64767eae136b547dffc49d28',
            'XJDGFD' => '0f7ed5e6ced827be2a39239b',
            'XFD' => 'cbbc3e85de19e34020db8cfc',
        ),
        'dev'=> array(
            'ZZJR' => 'zhangzhong',
            'XJDGFD' => 'treefinance',
            'XFD' => 'angliadddealtest',
        ),
        'producttest'=> array(
            'ZZJR' => '64767eae136b547dffc49d28',
            'XJDGFD' => 'treefinance',
            'XFD' => 'angliadddealtest',
        ),
        'test'=> array(
            'ZZJR' => 'zhangzhong',
            'XJDGFD' => 'treefinance',
            'XFD' => 'angliadddealtest',
        ),

    );
    public static function getAllowCliectId() {
        $env = app_conf('ENV_FLAG');
        return self::$_ALLOW_CLIENT_ID[$env];
    }
    public static function getAllowPlateormClientId ($client_id) {
        if (empty($client_id)) {
            return false;
        }
        $env = app_conf('ENV_FLAG');
        $platform = array_search($client_id,self::$_ALLOW_PLATEORM_CLIENT_ID[$env]);
        return $platform;
    }
}
