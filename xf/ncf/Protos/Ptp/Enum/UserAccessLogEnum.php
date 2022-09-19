<?php
namespace NCFGroup\Protos\Ptp\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserAccessLogEnum extends AbstractEnum {

    //topic
    const USER_ACCESS_LOG_TOPIC = 'user_access_log';

    //日志类型
    const TYPE_REGISTER = 1;//注册
    const TYPE_LOGIN = 2;//登陆
    const TYPE_LOGOUT = 3;//退出登陆
    const TYPE_REAL_NAME_AUTH = 4;//实名认证
    const TYPE_BIND_BANK_CARD = 5;//绑卡
    const TYPE_UPDATE_BANK_CARD = 6;//换卡
    const TYPE_UNBIND_BANK_CARD = 7;//解绑卡
    const TYPE_UPDATE_PASSWORD = 8;//修改密码
    const TYPE_UPDATE_MOBILE = 9;//修改手机号
    const TYPE_OPEN_P2P_ACCOUNT = 10;//开通存管账户
    const TYPE_CHARGE = 11;//充值
    const TYPE_WITHDRAW = 12;//提现

    public static $typeName = [
        self::TYPE_REGISTER             => '注册',
        self::TYPE_LOGIN                => '登陆',
        self::TYPE_LOGOUT               => '退出登陆',
        self::TYPE_REAL_NAME_AUTH       => '实名认证',
        self::TYPE_BIND_BANK_CARD       => '绑定银行卡',
        self::TYPE_UPDATE_BANK_CARD     => '更换银行卡',
        self::TYPE_UNBIND_BANK_CARD     => '解绑银行卡',
        self::TYPE_UPDATE_PASSWORD      => '修改密码',
        self::TYPE_UPDATE_MOBILE        => '修改手机号',
        self::TYPE_OPEN_P2P_ACCOUNT     => '开通存管账户',
        self::TYPE_CHARGE               => '充值',
        self::TYPE_WITHDRAW             => '提现',
    ];

    //设备见DeviceEnum

    //日志状态
    const STATUS_INIT = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;

    public static $statusDesc = [
        self::STATUS_INIT       => '初始状态',
        self::STATUS_SUCCESS    => '成功',
        self::STATUS_FAIL       => '失败',
    ];

    //平台
    const PLATFORM_WX = 1;
    const PLATFORM_P2P = 2;

    public static $platformName = [
        self::PLATFORM_WX       => '网信',
        self::PLATFORM_P2P      => '普惠',
    ];

    //充值渠道
    const CHARGE_CHANNEL_UCFPAY = 1; //先锋支付
    const CHARGE_CHANNEL_YEEPAY = 2; //易宝支付
    const CHARGE_CHANNEL_SUPERVISION = 3; //存管
    const CHARGE_CHANNEL_OFFLINE = 4; //大额充值

    //上报类型映射表
    public static $reportTypeMap = [
        self::TYPE_CHARGE => 'reportCharge',
        self::TYPE_WITHDRAW => 'reportWithdraw',
        self::TYPE_BIND_BANK_CARD => 'reportBind',
        self::TYPE_UPDATE_PASSWORD => 'reportCPwd',
        self::TYPE_UPDATE_BANK_CARD => 'reportChangeCard',
        self::TYPE_UPDATE_MOBILE => 'reportChangeMobile',
    ];

}
