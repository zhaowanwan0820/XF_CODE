<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserAccountEnum extends AbstractEnum
{
    // 用户账户是否 接入监管
    const ACCOUNT_IS_SUPERVISION = 1;
    // 平台类型
    const PLATFORM_SUPERVISION = 1; // 存管
    const PLATFORM_WANGXIN = 2; // 网信账户

    // 账户操作人类型
    const MAJOR_TYPE_SELF = 1;    // 法定代表人本人操作账户
    const MAJOR_TYPE_PROXY = 2;   // 授权代理人操作账户

    // 营业执照类型
    const CREDENTIALS_TYPE_LICENSE = 1;     // 三证合一营业执照
    const CREDENTIALS_TYPE_LICENSE_NEW = 3; // 营业执照

    // 企业证件有效截止时间默认值
    public static $credentialsExpireAtDefault='2070-12-31';

    // 账户类型
    const ACCOUNT_MIX                  = 0; // 借贷混合用户
    const ACCOUNT_INVESTMENT           = 1; // 投资户
    const ACCOUNT_FINANCE              = 2; // 融资户
    const ACCOUNT_ADVISORY             = 3; // 咨询户
    const ACCOUNT_GUARANTEE            = 4; // 担保户
    const ACCOUNT_CHANNEL              = 5; // 渠道户
    const ACCOUNT_CHANNELFICTILE       = 6; // 渠道虚拟户
    const ACCOUNT_PURCHASE             = 7; // 资产收购户
    const ACCOUNT_REPLACEPAY           = 8; // 代垫户
    const ACCOUNT_CAPITAL              = 9; // 受托资产管理户
    const ACCOUNT_TRADECENTER          = 10; // 交易中心（所）
    const ACCOUNT_PLATFORM             = 11; // 平台户
    const ACCOUNT_DEPOSIT              = 12; // 保证金户
    const ACCOUNT_PAY                  = 13; // 支付户
    const ACCOUNT_COUPON               = 14; // 投资券户
    const ACCOUNT_BONUS                = 15; // 红包户
    const ACCOUNT_RECHARGE             = 16; // 代充值户
    const ACCOUNT_LOAN                 = 17; // 放贷户
    const ACCOUNT_LOANING              = 18; // 垫资户
    const ACCOUNT_MANAGEMENT           = 19; // 管理户
    const ACCOUNT_MERCHANT             = 20; // 商户账户
    const ACCOUNT_MARKETINGSUBSIDY     = 21; // 营销补贴户

    //行业类型
    const INDUSTRY_STATE               = 0; //政府机关
    const INDUSTRY_POST                = 1; //邮政编码
    const INDUSTRY_IT                  = 2; //IT行业
    const INDUSTRY_TRADE               = 3; //商业贸易
    const INDUSTRY_BANK                = 4; //银行证券保险投资
    const INDUSTRY_COMMERCE            = 5; //工商税务
    const INDUSTRY_CONSULT             = 6; //咨询
    const INDUSTRY_SOCIAL              = 7; //社会服务
    const INDUSTRY_TRAVEL              = 8; //旅游酒店
    const INDUSTRY_HEALTH              = 9; //健康医疗
    const INDUSTRY_HOUSE               = 10; //房地产
    const INDUSTRY_TRANSPORT           = 11; //交通运输
    const INDUSTRY_CULTURE             = 12; //文化娱乐体育
    const INDUSTRY_ADVERTISE           = 13; //媒介广告
    const INDUSTRY_EDUCATE             = 14; //科研教育
    const INDUSTRY_AGRICULTURE         = 15; //农林牧渔
    const INDUSTRY_MAKE                = 16; //制造业
    const INDUSTRY_OTHER               = 20; //其他

    //行业类型
    public static $inducateTypes = [
        self::INDUSTRY_STATE       => '政府机关',
        self::INDUSTRY_POST        => '邮政编码',
        self::INDUSTRY_IT          => 'IT业',
        self::INDUSTRY_TRADE       => '商业贸易',
        self::INDUSTRY_BANK        => '银行证券保险投资',
        self::INDUSTRY_COMMERCE    =>'工商税务',
        self::INDUSTRY_CONSULT     =>'咨询',
        self::INDUSTRY_SOCIAL      =>'社会服务',
        self::INDUSTRY_TRAVEL      =>'旅游酒店',
        self::INDUSTRY_HEALTH      =>'健康医疗',
        self::INDUSTRY_HOUSE       =>'房地产',
        self::INDUSTRY_TRANSPORT   =>'交通运输',
        self::INDUSTRY_CULTURE     =>'文化娱乐体育',
        self::INDUSTRY_ADVERTISE   =>'媒介广告',
        self::INDUSTRY_EDUCATE     =>'科研教育',
        self::INDUSTRY_AGRICULTURE =>'农林牧渔',
        self::INDUSTRY_MAKE        =>'制造业',
        self::INDUSTRY_OTHER       =>'其他'

    ];

    // 平台类型描述
    public static $platformDesc = [
        self::PLATFORM_WANGXIN => '网信',
        self::PLATFORM_SUPERVISION => '存管',
    ];

    // 超级账户映射关系
    public static $accountWangxinMap = [
        self::ACCOUNT_MIX               => '06',
        self::ACCOUNT_INVESTMENT        => '01',
        self::ACCOUNT_FINANCE           => '02',
        self::ACCOUNT_ADVISORY          => '04',
        self::ACCOUNT_GUARANTEE         => '03',
        self::ACCOUNT_CHANNEL           => '',
        self::ACCOUNT_CHANNELFICTILE    => '',
        self::ACCOUNT_PURCHASE          => '03',
        self::ACCOUNT_REPLACEPAY        => '03',
        self::ACCOUNT_CAPITAL           => '',
        self::ACCOUNT_TRADECENTER       => '',
        self::ACCOUNT_PLATFORM          => '05',
        self::ACCOUNT_DEPOSIT           => '',
        self::ACCOUNT_PAY               => '',
        self::ACCOUNT_COUPON            => '12',
        self::ACCOUNT_BONUS             => '12',
        self::ACCOUNT_RECHARGE          => '03',
        self::ACCOUNT_LOAN              => '',
        self::ACCOUNT_LOANING           => '13',
        self::ACCOUNT_MANAGEMENT        => '10',
        self::ACCOUNT_MERCHANT          => '',
        self::ACCOUNT_MARKETINGSUBSIDY  => '12',

    ];

    // 存管账户映射关系
    public static $accountSupervisionMap = [
        self::ACCOUNT_MIX               => '06',
        self::ACCOUNT_INVESTMENT        => '01',
        self::ACCOUNT_FINANCE           => '02',
        self::ACCOUNT_ADVISORY          => '04',
        self::ACCOUNT_GUARANTEE         => '03',
        self::ACCOUNT_CHANNEL           => '',
        self::ACCOUNT_CHANNELFICTILE    => '',
        self::ACCOUNT_PURCHASE          => '03',
        self::ACCOUNT_REPLACEPAY        => '03',
        self::ACCOUNT_CAPITAL           => '',
        self::ACCOUNT_TRADECENTER       => '',
        self::ACCOUNT_PLATFORM          => '05',
        self::ACCOUNT_DEPOSIT           => '',
        self::ACCOUNT_PAY               => '',
        self::ACCOUNT_COUPON            => '12',
        self::ACCOUNT_BONUS             => '12',
        self::ACCOUNT_RECHARGE          => '03',
        self::ACCOUNT_LOAN              => '',
        self::ACCOUNT_LOANING           => '13',
        self::ACCOUNT_MANAGEMENT        => '10',
        self::ACCOUNT_MERCHANT          => '',
        self::ACCOUNT_MARKETINGSUBSIDY  => '12',
    ];

    // 账户类型描述
    public static $accountDesc = [
        self::PLATFORM_WANGXIN => [
            self::ACCOUNT_MIX               => '借贷混合用户',
            self::ACCOUNT_INVESTMENT        => '投资户',
            self::ACCOUNT_FINANCE           => '融资户',
            self::ACCOUNT_ADVISORY          => '咨询户',
            self::ACCOUNT_GUARANTEE         => '担保/代偿I户',
            self::ACCOUNT_CHANNEL           => '渠道户',
            self::ACCOUNT_CHANNELFICTILE    => '渠道虚拟户',
            self::ACCOUNT_PURCHASE          => '资产收购户',
            self::ACCOUNT_REPLACEPAY        => '担保/代偿II-b户',
            self::ACCOUNT_CAPITAL           => '受托资产管理户',
            self::ACCOUNT_TRADECENTER       => '交易中心（所）',
            self::ACCOUNT_PLATFORM          => '平台户',
            self::ACCOUNT_DEPOSIT           => '保证金户',
            self::ACCOUNT_PAY               => '支付户',
            self::ACCOUNT_COUPON            => '投资券户',
            self::ACCOUNT_BONUS             => '红包户',
            self::ACCOUNT_RECHARGE          => '担保/代偿II-a户',
            self::ACCOUNT_LOAN              => '放贷户',
            self::ACCOUNT_LOANING           => '垫资户',
            self::ACCOUNT_MANAGEMENT        => '管理户',
            self::ACCOUNT_MERCHANT          => '商户账户',
            self::ACCOUNT_MARKETINGSUBSIDY  => '营销补贴户',
        ],
        self::PLATFORM_SUPERVISION => [
            self::ACCOUNT_MIX               => '借贷混合户',
            self::ACCOUNT_INVESTMENT        => '网贷账户',
            self::ACCOUNT_FINANCE           => '网贷借款户',
            self::ACCOUNT_ADVISORY          => '网贷咨询户',
            self::ACCOUNT_GUARANTEE         => '网贷担保/代偿I户',
            self::ACCOUNT_CHANNEL           => '渠道户',
            self::ACCOUNT_CHANNELFICTILE    => '渠道虚拟户',
            self::ACCOUNT_PURCHASE          => '网贷担保/代偿I户',
            self::ACCOUNT_REPLACEPAY        => '网贷担保/代偿I户',
            self::ACCOUNT_CAPITAL           => '受托资产管理户',
            self::ACCOUNT_TRADECENTER       => '交易中心（所）',
            self::ACCOUNT_PLATFORM          => '平台户',
            self::ACCOUNT_DEPOSIT           => '保证金户',
            self::ACCOUNT_PAY               => '支付户',
            self::ACCOUNT_COUPON            => '投资券户',
            self::ACCOUNT_BONUS             => '网贷营销账户',
            self::ACCOUNT_RECHARGE          => '网贷担保/代偿I户',
            self::ACCOUNT_LOAN              => '放贷户',
            self::ACCOUNT_LOANING           => '网贷垫资户',
            self::ACCOUNT_MANAGEMENT        => '网贷收费户',
            self::ACCOUNT_MERCHANT          => '商户账户',
            self::ACCOUNT_MARKETINGSUBSIDY  => '网贷营销账户',
        ]
    ];
}
