<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class PaymentEnum extends AbstractEnum {

    /**
     * 充值来源-WEB
     * @var int
     */
    const PLATFORM_WEB = 1;

    /**
     * 充值来源-Android
     * @var int
     */
    const PLATFORM_ANDROID = 2;

    /**
     * 充值来源-IOS
     * @var int
     */
    const PLATFORM_IOS = 3;

    /**
     * 充值来源-移动Web
     * @var int
     */
    const PLATFORM_MOBILEWEB = 4;

    /**
     * 充值来源-后台
     * @var int
     */
    const PLATFORM_ADMIN = 5;

    /**
     * 充值来源-pos
     * @var int
     */
    const PLATFORM_POS = 6;

    /**
     * 充值来源-线下充值
     * @var int
     */
    const PLATFORM_OFFLINE = 7;

    /**
     * 充值来源-H5
     * @var int
     */
    const PLATFORM_H5 = 8;

    /**
     * 充值来源-工资宝
     * @var int
     */
    const PLATFORM_SALARY = 9;

    /**
     * 充值来源-开放平台退款
     * @var int
     */
    const PLATFORM_REFUND= 10;

    /**
     * 充值来源-易宝支付
     * @var int
     */
    const PLATFORM_YEEPAY = 11;

    /**
     * 充值来源 - 绑卡认证费用
     * @var int
     */
    const PLATFORM_AUTHCARD = 12;

    //来自第三方平台web
    const PLATFORM_WEB_THIRD = 13;

    // 充值来源 - 来自基金赎回
    const PLATFORM_FUND_REDEEM = 14;

    // 业务来源 - 来自理财师客户端
    const PLATFORM_LCS = 15;

    // 业务来源 - 存管
    const PLATFORM_SUPERVISION = 16;

    // 业务来源 - 存管自动扣款充值代扣
    const PLATFORM_SUPERVISION_AUTORECHARGE = 17;

    // 业务来源 - 大额充值
    const PLATFORM_OFFLINE_V2 = 18;

    // 业务来源 - 新协议支付
    const PLATFORM_H5_NEW_CHARGE = 19;

    // 业务来源 - 企业用户充值H5
    const PLATFORM_ENTERPRISE_H5CHARGE = 20;

    // 业务来源 - App充值限额后改用PC网银充值
    const PLATFORM_APPTOPC_CHARGE = 21;

    const REGISTER_HASREGISTER = 0; // 支付平台已经开户
    const REGISTER_SUCCESS = 1; //支付平台开户成功
    const REGISTER_FAILURE = 2; //支付平台开户失败

    //用户已存在状态
    const REGISTER_USER_EXISTS = '31';

    const CHARGE_PENDING = '02';//待处理
    const CHARGE_SUCCESS = '00';//成功
    const CHARGE_FAILURE = '01';//失败

    const API_RESPONSE_SUCCESS = 'S';
    const API_RESPONSE_FAIL = 'F';

    const ERROR_PAYMENT_ORDER_NOTEXITS = "10";
    const ERROR_PAYMENT_API = "11";

    //认证类型映射表
    private static $cert_status_map = array(
        'EXTERNAL_CERT' => 1, //IVR语音认证
        'FASTPAY_CERT'  => 2, //快捷认证(四要素认证)
        'TRANSFER_CERT' => 3, //转账认证
    );

    /**
     * 充值状态-未支付
     * @var int
     */
    const IS_PAID_NO = 0;

    /**
     * 充值状态-支付成功
     * @var int
     */
    const IS_PAID_SUCCESS = 1;

    /**
     * 充值状态-待支付
     * @var int
     */
    const IS_PAID_ING = 2;

    /**
     * 充值状态-支付失败
     * @var int
     */
    const IS_PAID_FAIL = 3;

    const AMOUNT_LIMIT_NULL = 0; // 未区分
    const AMOUNT_LIMIT_SMALL = 1; // 小额
    const AMOUNT_LIMIT_BIG = 2; // 大额

    const PAYMENT_YEEPAY = 3;
    const PAYMENT_UCFPAY = 4;

    //线上充值来源
    public static $onlinePlatform = [
        self::PLATFORM_WEB,
        self::PLATFORM_ANDROID,
        self::PLATFORM_IOS,
        self::PLATFORM_MOBILEWEB,
        self::PLATFORM_H5,
        self::PLATFORM_SUPERVISION,
        self::PLATFORM_H5_NEW_CHARGE,
    ];

    //wap端充值来源
    public static $wapPlatform = [
        self::PLATFORM_H5,
        self::PLATFORM_H5_NEW_CHARGE,
        self::PLATFORM_MOBILEWEB,
        self::PLATFORM_SUPERVISION,
    ];
    //pc端充值来源
    public static $pcPlatform = [
        self::PLATFORM_WEB,
        self::PLATFORM_OFFLINE,
        self::PLATFORM_OFFLINE_V2,
    ];

    //大额充值来源
    public static $offlinePlatform = [
        self::PLATFORM_OFFLINE_V2,
        self::PLATFORM_APPTOPC_CHARGE,
    ];

    const TRIGGER_CHARGE_ONLINE = 19; // 快捷充值（线上）
    const TRIGGER_CHARGE_OFFLINE = 20; // 大额充值（线下）
}
