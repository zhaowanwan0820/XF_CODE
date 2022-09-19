<?php
namespace NCFGroup\Protos\Life\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class TripEnum extends AbstractEnum {
    // 商户简称-AA出行
    const MERCHANTID_AACX = 'aacx';

    // 出行平台-AA租车
    const PLATFORM_AAZC = 1;

    // 出行平台名称-AA租车
    const PLATFORM_NAME_AAZC = 'aazuche';

    // 出行平台列表配置
    public static $tripPlatformConfig = [
        self::PLATFORM_AAZC => self::PLATFORM_NAME_AAZC
    ];

    // 后台配置-同一出行时间段内，同一身份证号及手机号下单次数限制
    const CONF_SAMETIME_ORDERCOUNT = 'WXCX_CONF_SAMETIME_ORDERCOUNT';
    // 后台配置-网信出行VIP等级配置
    const CONF_VIP_GRADE = 'WXCX_CONF_VIP_GRADE';
    // 后台配置-订单超时秒数
    const CONF_ORDER_OVERTIME_SECONDS = 'WXCX_CONF_ORDER_OVERTIME_SECONDS';
    // 后台风控规则-普通用户风险系数
    const RISK_PRICE_RATE_COMMON = 'WXCX_RISK_PRICE_RATE_COMMON';
    // 后台风控规则-黑名单用户风险系数
    const RISK_PRICE_RATE_BLACK = 'WXCX_RISK_PRICE_RATE_BLACK';
    // 站内信开关
    const CONF_MSG_BOX_ENABLE = 'MSG_BOX_ENABLE';
    // 红包开关
    const CONF_BONUS_ENABLE = 'BONUS_ENABLE';
    // 虚拟号码开关
    const CONF_VIRTURL_NUMBER_ENABLE = 'VIRTURL_NUMBER_ENABLE';
    // 红包出资方用户ID
    const CONF_WXCX_BONUS_COMPANY_UID = 'WXCX_BONUS_COMPANY_UID';
    // 红包有效期天数
    const CONF_WXCX_BONUS_EXPIRE_DAY = 'WXCX_BONUS_EXPIRE_DAY';
    // 出行取消订单的规则配置
    const CONF_WXCX_CANCEL_RULE = 'WXCX_CANCEL_RULE';
    // 出行开票说明的规则配置
    const CONF_WXCX_INVOICE_DESCRIBE = 'WXCX_INVOICE_DESCRIBE';
    // 后台配置-收银台订单超时秒数
    const CONF_PAYMENT_ORDER_OVERTIME_SECONDS = 'PAYMENT_ORDER_OVERTIME_SECONDS';
    // 广告位-出行授权详情页
    const ADV_TRIP_AUTH_PROTOCOL = 'trip_auth_protocol';

    // 出行订单默认的超时秒数
    const TRIP_DEFAULT_OVERTIME_SECONDS = 1800;
    // 出行随叫随到订单的超时秒数
    const TRIP_CALL_OVERTIME_SECONDS = 300;
    // 出行非随叫随到用车时间-下单时间小于90m订单的超时秒数
    const TRIP_NOT_CALL_SMALL_OVERTIME_SECONDS = 900;
    // 出行非随叫随到用车时间-下单时间大于90m订单的超时秒数
    const TRIP_NOT_CALL_BIG_OVERTIME_SECONDS = 4500;

    // 状态-连续扣款失败
    const TRIP_STATUS_PAY_FAILED      = -2;
    // 状态-AA下单失败
    const TRIP_STATUS_AA_FAILED       = -1;
    // 状态-网信下单成功
    const TRIP_STATUS_WX_SUCCESS      = 0;
    // 状态-AA下单成功
    const TRIP_STATUS_AA_SUCCESS      = 1;
    // 状态-司机确定接单
    const TRIP_STATUS_DRIVER_CONFIRM  = 2;
    // 状态-司机出发去往上车地点
    const TRIP_STATUS_DRIVER_LEAVE    = 3;
    // 状态-司机到达上车地点
    const TRIP_STATUS_DRIVER_ARRIVE   = 4;
    // 状态-乘客上车（已废弃）
    //const TRIP_STATUS_USER_ABOARD     = 5;
    // 状态-司机开始服务
    const TRIP_STATUS_DRIVER_START    = 6;
    // 状态-司机结束服务
    const TRIP_STATUS_DRIVER_FINISH   = 7;
    // 状态-司机确认订单
    const TRIP_STATUS_DRIVER_COMMIT   = 8;
    // 状态-订单完成
    const TRIP_STATUS_USER_PAYSUCCESS = 9;
    // 状态-支付失败
    //const TRIP_STATUS_USER_PAYFAILED  = 10;
    // 状态-用户取消订单
    const TRIP_STATUS_USER_CANCEL     = 11;
    // 状态-客服取消订单
    const TRIP_STATUS_CS_CANCEL       = 12;
    // 状态-客服更换司机
    const TRIP_STATUS_CS_CHANGEDRIVER = 13;

    // 类型-用户取消
    const TRIP_CANCEL_TYPE_USER_CANCEL = 1;
    // 类型-系统取消
    const TRIP_CANCEL_TYPE_XT_CANCEL   = 2;
    // 出行订单-商品名称
    const TRIP_GOODS_NAME = '网信出行';

    /**
     * 订单状态列表配置
     * @var array
     */
    public static $tripStatusConfig = [
        self::TRIP_STATUS_AA_FAILED       => 'AA下单失败',
        self::TRIP_STATUS_WX_SUCCESS      => '网信下单成功',
        self::TRIP_STATUS_AA_SUCCESS      => '用户下单成功',
        self::TRIP_STATUS_DRIVER_CONFIRM  => '司机确定接单',
        self::TRIP_STATUS_DRIVER_LEAVE    => '司机出发去往上车地点',
        self::TRIP_STATUS_DRIVER_ARRIVE   => '司机到达上车地点',
        //self::TRIP_STATUS_USER_ABOARD     => '乘客上车',
        self::TRIP_STATUS_DRIVER_START    => '司机开始服务',
        self::TRIP_STATUS_DRIVER_FINISH   => '司机结束服务',
        self::TRIP_STATUS_DRIVER_COMMIT   => '司机确认订单',
        self::TRIP_STATUS_USER_PAYSUCCESS => '订单完成',
        //self::TRIP_STATUS_USER_PAYFAILED  => '支付失败',
        self::TRIP_STATUS_USER_CANCEL     => '用户取消订单',
        self::TRIP_STATUS_CS_CANCEL       => '客服取消订单',
        self::TRIP_STATUS_CS_CHANGEDRIVER => '客服更换司机',
    ];

    /**
     * 订单状态-已下单配置
     * @var array
     */
    public static $tripStatusCreate = [
        self::TRIP_STATUS_WX_SUCCESS,
        self::TRIP_STATUS_AA_SUCCESS,
    ];

    /**
     * 订单状态-进行中配置
     * @var array
     */
    public static $tripStatusIng = [
        //self::TRIP_STATUS_WX_SUCCESS,
        self::TRIP_STATUS_AA_SUCCESS,
        self::TRIP_STATUS_DRIVER_CONFIRM,
        self::TRIP_STATUS_DRIVER_LEAVE,
        self::TRIP_STATUS_DRIVER_ARRIVE,
        //self::TRIP_STATUS_USER_ABOARD,
        self::TRIP_STATUS_DRIVER_START,
        self::TRIP_STATUS_DRIVER_FINISH,
        self::TRIP_STATUS_DRIVER_COMMIT,
    ];

    /**
     * 可以绑定虚拟号码的状态列表
     * @var array
     */
    public static $tripStatusVirtualNumber = [
        self::TRIP_STATUS_DRIVER_CONFIRM,
        self::TRIP_STATUS_DRIVER_LEAVE,
        self::TRIP_STATUS_DRIVER_ARRIVE,
        //self::TRIP_STATUS_USER_ABOARD,
        self::TRIP_STATUS_DRIVER_START,
        self::TRIP_STATUS_DRIVER_FINISH,
        self::TRIP_STATUS_DRIVER_COMMIT,
    ];

    /**
     * 订单状态-已完成配置
     * @var array
     */
    public static $tripStatusDone = [
        self::TRIP_STATUS_USER_PAYSUCCESS,
    ];

    /**
     * 订单状态-已取消配置
     * @var array
     */
    public static $tripStatusCancel = [
        self::TRIP_STATUS_USER_CANCEL,
        self::TRIP_STATUS_CS_CANCEL,
    ];

    /**
     * 订单取消-取消类型
     * @var array
     */
    public static $tripCancelType = [
        self::TRIP_CANCEL_TYPE_USER_CANCEL => '用户取消',
        self::TRIP_CANCEL_TYPE_XT_CANCEL => '系统取消',
    ];

    //开票类型
    const TRIP_HAS_INVOICE = 1; //已开票
    const TRIP_NOT_INVOICE = 0; //未开票

    /**
     * 订单取消-开票类型
     * @var array
     */
    public static $tripVoiceType = [
        self::TRIP_HAS_INVOICE => '已开票',
        self::TRIP_NOT_INVOICE => '未开票',
    ];

    // 发票标识-纸质发票
    const INVOICE_FLAG_PAPER = 1;

    // 发票标识-电子发票
    const INVOICE_FLAG_ELECTRON = 2;

    // 发票类型-个人
    const INVOICE_TYPE_PERSONAL = 1;

    // 发票类型-企业
    const INVOICE_TYPE_ENTERPRISE = 2;

    // 发票内容-客运服务费
    const TYPEID_PASSENGER_SERVICE = 1;

    /**
     * 发票内容配置
     * @var array
     */
    public static $tripTypeConfig = [
        self::TYPEID_PASSENGER_SERVICE => '客运服务费',
    ];

    // 状态-未处理
    const STATUS_UNDISPOSE = 0;

    // 状态-请求处理中
    const STATUS_REQUEST_ING = 1;

    // 状态-第三方已受理
    const STATUS_INVOICE_ACCEPT = 2;

    // 状态-发票已开出
    const STATUS_INVOICE_SUCCESS = 3;

    // 状态-开票失败
    const STATUS_INVOICE_FAILED = 4;

    /**
     * 发票状态配置
     * @var array
     */
    public static $invoiceStatusConfig = [
        self::STATUS_UNDISPOSE       => '已受理',
        self::STATUS_REQUEST_ING     => '已受理',
        self::STATUS_INVOICE_ACCEPT  => '已受理',
        self::STATUS_INVOICE_SUCCESS => '已邮寄',
        self::STATUS_INVOICE_FAILED  => '开票失败',
    ];

    // 是否有效-无效
    const EFFECT_NO  = 0;
    // 是否有效-有效
    const EFFECT_YES = 1;

    // 城市编码-北京
    const CITYCODE_BEIJING   = 1;
    // 城市编码-深圳
    const CITYCODE_SHENZHEN  = 12;
    // 城市编码-天津
    const CITYCODE_TIANJIN   = 13;
    // 城市编码-上海
    const CITYCODE_SHANGHAI  = 15;
    // 城市编码-广州
    const CITYCODE_GUANGZHOU = 16;
    // 城市编码-大连
    const CITYCODE_DALIAN    = 17;
    // 城市编码-青岛
    const CITYCODE_QINGDAO   = 18;
    // 城市编码-太原
    const CITYCODE_TAIYUAN   = 20;
    // 城市编码-杭州
    const CITYCODE_HANGZHOU  = 21;
    // 城市编码-成都
    const CITYCODE_CHENGDU   = 22;
    // 城市编码-三亚
    const CITYCODE_SANYA     = 23;
    // 城市编码-呼和浩特
    const CITYCODE_HUHEHAOTE = 26;
    // 城市编码-西安
    const CITYCODE_XIAN      = 27;
    // 城市编码-沈阳
    const CITYCODE_SHENYANG  = 28;
    // 城市编码-长沙
    const CITYCODE_CHANGSHA  = 34;

    /**
     * 城市编码列表配置
     * @var array
     */
    public static $cityCodeConfig = [
        self::CITYCODE_BEIJING   => '北京',
        self::CITYCODE_SHENZHEN  => '深圳',
        self::CITYCODE_TIANJIN   => '天津',
        self::CITYCODE_SHANGHAI  => '上海',
        self::CITYCODE_GUANGZHOU => '广州',
        self::CITYCODE_DALIAN    => '大连',
        self::CITYCODE_QINGDAO   => '青岛',
        self::CITYCODE_TAIYUAN   => '太原',
        self::CITYCODE_HANGZHOU  => '杭州',
        self::CITYCODE_CHENGDU   => '成都',
        self::CITYCODE_SANYA     => '三亚',
        self::CITYCODE_HUHEHAOTE => '呼和浩特',
        self::CITYCODE_XIAN      => '西安',
        self::CITYCODE_SHENYANG  => '沈阳',
        self::CITYCODE_CHANGSHA  => '长沙',
    ];

    /**
     * 城市&地区编码映射配置
     * @var array
     */
    public static $areaCodeConfig = [
        self::CITYCODE_BEIJING   => '010',
        self::CITYCODE_SHENZHEN  => '755',
        self::CITYCODE_TIANJIN   => '022',
        self::CITYCODE_SHANGHAI  => '021',
        self::CITYCODE_GUANGZHOU => '020',
        self::CITYCODE_DALIAN    => '411',
        self::CITYCODE_QINGDAO   => '532',
        self::CITYCODE_TAIYUAN   => '351',
        self::CITYCODE_HANGZHOU  => '571',
        self::CITYCODE_CHENGDU   => '028',
        self::CITYCODE_SANYA     => '8981',
        self::CITYCODE_HUHEHAOTE => '471',
        self::CITYCODE_XIAN      => '029',
        self::CITYCODE_SHENYANG  => '024',
        self::CITYCODE_CHANGSHA  => '7311',
    ];

    // 服务类型-接机服务
    const SERVICETYPE_AIRPORT_RECEIVE = 2;
    // 服务类型-送机服务
    const SERVICETYPE_AIRPORT_ARRIVE  = 3;
    // 服务类型-预约用车
    const SERVICETYPE_BESPEAK         = 4;
    // 服务类型-随叫随到/立即用车
    const SERVICETYPE_WHENEVER        = 5;
    // 服务类型-接站服务
    const SERVICETYPE_TRAIN_RECEIVE   = 13;
    // 服务类型-送站服务
    const SERVICETYPE_TRAIN_ARRIVE    = 14;

    /**
     * 服务类型列表配置
     * @var array
     */
    public static $serviceTypeConfig = [
        self::SERVICETYPE_AIRPORT_RECEIVE => '接机服务',
        self::SERVICETYPE_AIRPORT_ARRIVE  => '送机服务',
        self::SERVICETYPE_BESPEAK         => '预约用车',
        self::SERVICETYPE_WHENEVER        => '立即用车',
        self::SERVICETYPE_TRAIN_RECEIVE   => '接站服务',
        self::SERVICETYPE_TRAIN_ARRIVE    => '送站服务',
    ];

    // 车型列表-经济车型
    const CARTYPE_ECONOMIC = 1;
    // 车型列表-舒适车型
    const CARTYPE_COMFORT  = 2;
    // 车型列表-豪华车型
    const CARTYPE_LUXURY   = 3;
    // 车型列表-商务车型
    const CARTYPE_BUSINESS = 4;
    // 车型列表-奢华车型
    const CARTYPE_PLATINUM = 5;
    // 车型列表-特斯拉(tesla)
    const CARTYPE_TESLA    = 6;

    /**
     * 车型列表配置
     * @var array
     */
    public static $carTypeConfig = [
        self::CARTYPE_ECONOMIC => '经济车型',
        self::CARTYPE_COMFORT => '舒适车型',
        self::CARTYPE_LUXURY => '豪华车型',
        self::CARTYPE_BUSINESS => '商务车型',
        self::CARTYPE_PLATINUM => '奢华车型',
        self::CARTYPE_TESLA => '特斯拉',
    ];

    // 支付状态-已受理
    const PAY_STATUS_ACCEPT    = 0;
    // 支付状态-成功
    const PAY_STATUS_SUCCESS   = 1;
    // 支付状态-失败
    const PAY_STATUS_FAILED    = 2;
    // 支付状态-处理中
    const PAY_STATUS_ING       = 3;
    // 支付状态-交易关闭
    const PAY_STATUS_CLOSE     = 4;
    // 支付状态-请求处理中
    const PAY_STATUS_REQUEST   = 5;

    // 网信出行-所有的支付状态列表
    public static $allPayStatusList = [
        self::PAY_STATUS_ACCEPT => '待支付',
        self::PAY_STATUS_SUCCESS => '支付成功',
        self::PAY_STATUS_FAILED => '支付失败',
        self::PAY_STATUS_ING => '处理中',
    ];

    // 网信出行-成功、处理中、已请求的支付状态列表
    public static $hasDisposePayStatusList = [
        self::PAY_STATUS_SUCCESS,
        self::PAY_STATUS_ING,
        self::PAY_STATUS_REQUEST,
    ];

    // 网信出行-成功、失败的支付状态列表
    public static $endPayStatusList = [
        self::PAY_STATUS_SUCCESS,
        self::PAY_STATUS_FAILED,
    ];

    // 分账状态-已分账
    const DIVISION_STATUS_YES = 1;
    // 分账状态-未分账
    const DIVISION_STATUS_NO = 0;

    // 短信、站内信模版-叫车成功，司机已经出发-本人乘车
    const TPL_SMS_LIFE_RECEIVED_INSTANT_SELF = 'SMS_LIFE_RECEIVED_INSTANT_SELF';
    // 短信、站内信模版-叫车成功，司机已经出发-乘车人
    const TPL_SMS_LIFE_RECEIVED_INSTANT_OTHER = 'SMS_LIFE_RECEIVED_INSTANT_OTHER';
    // 短信、站内信模版-叫车成功，司机已经出发-订车人
    const TPL_SMS_LIFE_RECEIVED_INSTANT_OTHER2 = 'SMS_LIFE_RECEIVED_INSTANT_OTHER2';
    // 短信、站内信模版-自己乘车，下单成功
    const TPL_SMS_LIFE_RECEIVED_BOOK_SELF = 'SMS_LIFE_RECEIVED_BOOK_SELF';
    // 短信、站内信模版-为他人下单成功-乘车人
    const TPL_SMS_LIFE_RECEIVED_BOOK_OTHER = 'SMS_LIFE_RECEIVED_BOOK_OTHER';
    // 短信、站内信模版-为他人下单成功-订车人
    const TPL_SMS_LIFE_RECEIVED_BOOK_OTHER2 = 'SMS_LIFE_RECEIVED_BOOK_OTHER2';
    // 短信、站内信模版-自己乘车，车辆已到达
    const TPL_SMS_LIFE_ARRIVE_SELF = 'SMS_LIFE_ARRIVE_SELF';
    // 短信、站内信模版-为他人叫车，车辆已到达-乘车人
    const TPL_SMS_LIFE_ARRIVE_OTHER = 'SMS_LIFE_ARRIVE_OTHER';
    // 短信、站内信模版-为他人叫车，车辆已到达-订车人
    const TPL_SMS_LIFE_ARRIVE_OTHER2 = 'SMS_LIFE_ARRIVE_OTHER2';
    // 短信、站内信模版-订单支付，包含红包
    const TPL_SMS_LIFE_PAYMENT_DISCOUNT = 'SMS_LIFE_PAYMENT_DISCOUNT';
    // 短信、站内信模版-订单支付，不含红包
    const TPL_SMS_LIFE_PAYMENT = 'SMS_LIFE_PAYMENT';
    // 短信、站内信模版-订单取消
    const TPL_SMS_LIFE_CANCEL = 'SMS_LIFE_CANCEL';
    // 短信、站内信模版-订单退款
    const TPL_SMS_LIFE_REFUND = 'SMS_LIFE_REFUND';
    // 短信、站内信模版-一直联系不到用户，司机结束订单
    const TPL_SMS_LIFE_END = 'SMS_LIFE_END';

    //为自己叫车
    const IS_SELF_YES = 1;
    //为他人叫车
    const IS_SELF_NO = 0;
    // 网信出行-为谁叫车列表
    public static $forPeopleList = [
        self::IS_SELF_YES => '自己叫车',
        self::IS_SELF_NO => '为他人叫车',
    ];

    const AWARD_TYPE_BONUS = 1;
    // 网信出行-奖励类型列表
    public static $awardTypeList = [
        self::AWARD_TYPE_BONUS => '红包',
    ];
    // 网信出行-奖励类型列表
    public static $awardStatusMap = [
        0 => '未发放',
        1 => '已发放',
        2 => '已消费',
        3 => '已过期',
        4 => '消费失败',
    ];

}