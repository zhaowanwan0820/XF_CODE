<?php
namespace NCFGroup\Protos\Life\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CommonEnum extends AbstractEnum {
    // 网信生活开关配置键值(0:关闭1:开启)
    const LIFE_SWITCH = 'LIFE_SWITCH';

    // Redis 键值配置
    const CACHEKEY_CONF_LASTTIME = 'lifeconf_last_update_time'; // 网信生活缓存最后更新时间
    const CACHEKEY_PAYMENT_USERCARD_LIST = 'life_payment_usercard_list_%s'; // 用户的银行卡列表

    // 网信出行-APP首页轮播api_conf表的键值
    const TRIP_APP_ADV_KEY = 'home_carousel';
    // 网信出行-APP首页固定入口api_conf表的键值
    const TRIP_APP_FIXED_KEY = 'home_trip';
    // 网信出行-APP首页广告位类型
    const TRIP_APP_ADV_TYPE = 29;
    // 网信出行-APP里面读取的客服电话键值
    const TRIP_APP_SERVICE_TEL_KEY = 'param_service_telephone';
    // 网信出行-默认的客服电话
    const TRIP_APP_SERVICE_TEL_DEFAULT = '400-890-9888';

    // Alarm 告警信息配置
    const LIFE_ALARM_P2P = 'life_p2p'; // 网信生活请求p2p，rpc调用异常使用
    const LIFE_ALARM_STATS = 'life_stats'; // 网信生活统计相关告警
    const LIFE_ALARM_WXCX = 'life_wxcx'; // 网信出行相关告警
    const LIFE_ALARM_SYNC_P2P = 'life_sync_p2p'; // 同步p2p到网信生活相关告警
    const LIFE_ALARM_JOBS = 'life_jobs'; // 网信生活jobs相关告警
    const PAYMENT_DUPLICATEPAY_ALARM_JOBS = 'Payment_DuplicatePay'; // 收银台发生重复支付相关告警
    const WXTRIP_DUPLICATEPAY_ALARM_JOBS = 'WxTrip_DuplicatePay'; // 网信出行发生重复支付相关告警
    const TRIP_REFUND_ALARM_JOBS = 'Trip_Refund'; // 网信出行发生退款失败相关告警
    const PAYMENT_PAY_ALARM = 'Payment_Pay'; // 收银台发生扣款失败相关告警
    const PAYMENT_REFUND_ALARM_JOBS = 'Payment_Refund'; // 收银台发生退款失败相关告警
    const PAYMENT_DIVISION_ALARM = 'Payment_Division'; // 收银台发生分账失败相关告警
    const PAYMENT_CHANGECARD_ALARM = 'Payment_ChangeCard'; // 收银台后台系统代扣更换银行卡付款的告警
    // Monitor 告警信息配置
    const LIFE_MONITOR_JOBS = 'LIFE_JOBSWORKER_RUN_FAILED'; // 网信生活jobs相关告警
    const PAYMENT_HAPPEND_REFUND = 'LIFE_PAYMENT_HAPPEND_REFUND'; // 收银台发生退款交易告警
    const PAYMENT_REFUND_FAILED = 'LIFE_PAYMENT_REFUND_FAILED'; // 收银台调用退款接口告警
    const PAYMENT_ORDER_QUERY_FAILED = 'LIFE_PAYMENT_ORDER_QUERY_FAILED'; // 收银台调用付款订单查询接口告警
    const PAYMENT_REFUND_QUERY_FAILED = 'LIFE_REFUND_ORDER_QUERY_FAILED'; // 收银台调用退款查询接口告警
    const PAYMENT_DIVISION_QUERY_FAILED = 'LIFE_DIVISION_ORDER_QUERY_FAILED'; // 收银台调用分账查询接口告警
    const LIFE_PAYMENT_PAY_NOTIFY = 'LIFE_PAYMENT_PAY_NOTIFY'; // 收银台-扣款结果(回调)次数
    const LIFE_PAYMENT_REFUND_NOTIFY = 'LIFE_PAYMENT_REFUND_NOTIFY'; // 收银台-退款结果(回调)次数
    const LIFE_PAYMENT_DIVISION_NOTIFY = 'LIFE_PAYMENT_DIVISION_NOTIFY'; // 收银台-分账结果(回调)次数
    const LIFE_PAYMENT_VERIFYPWD_NOTIFY = 'LIFE_PAYMENT_VERIFYPWD_NOTIFY'; // 收银台-扣款验密结果(回调)次数
    const TRIP_REFUND_FAILED = 'LIFE_TRIP_REFUND_FAILED'; // 网信出行-调用退款接口告警
    const TRIP_ORDER_NOTIFY = 'LIFE_TRIP_ORDER_NOTIFY'; // 网信出行-下单结果(回调)次数
    const TRIP_ORDER_STATUS_NOTIFY = 'LIFE_TRIP_STATUS_NOTIFY'; // 网信出行-下单状态(回调)次数
    const TRIP_INVOICE_NOTIFY = 'LIFE_TRIP_INVOICE_NOTIFY'; // 网信出行-发票结果(回调)次数
    const TRIP_PAY_NOTIFY = 'LIFE_TRIP_PAY_NOTIFY'; // 网信出行-付款状态(回调)次数

    // ABControl白名单配置
    const ABCONTROL_TRIP_LIST = 'triplist'; // 出行白名单
    const ABCONTROL_FM_LIST = 'financialManagerList'; // 理财师白名单
    const ABCONTROL_TRIP_STAFF_LIST = 'tripStaffList'; // 内部员工白名单

    // 币种
    const LIFE_CURRENCY = 156;
    // 网信生活跟商户的分账比例
    const LIFE_DIVISION_RATE = '0.1';

    // 交易类型(0:网信出行)
    const LOG_DEAL_TYPE_TRIP = 0;
    // 交易描述-出行车费
    const LOG_INFO_TRIP_MONEY = '消费-出行车费';
    // 交易描述-出行车费
    const LOG_INFO_TRIP_REFUND_MONEY = '消费-车费退款';
    // 交易描述-取消车费
    const LOG_INFO_TRIP_CANCEL_MONEY = '消费-取消车费';
    // 用户交易记录的类型
    public static $logInfoList = array(self::LOG_INFO_TRIP_MONEY, self::LOG_INFO_TRIP_REFUND_MONEY, self::LOG_INFO_TRIP_CANCEL_MONEY);
    // 用户交易记录的类型对应的简写
    public static $logInfoListAbbreviation = array(
        self::LOG_INFO_TRIP_MONEY => '支',
        self::LOG_INFO_TRIP_CANCEL_MONEY => '支',
        self::LOG_INFO_TRIP_REFUND_MONEY => '收',
    );

    // 后台配置列表，特殊的配置键值列表
    public static $specialConfKeyList = array(
        'WXCX_CONF_VIP_GRADE',
    );
    // 过滤规则
    public static $queryInjectPatterns = array(
        "/insert\s+/i",
        "/update\s+.*?\s+set\s*=.*/i",
        "/delete\s+from.*/i",
        "/truncate\s+/i",
        "/\s+drop\s+/i",
        "/union\(.*/i",
        "/union\s+select.*/i",
        "/varchar\(\d+\)/i",
        "/\s+declare\s+/i",
        "/\schar\(/i",
        "/\s+char\s+/i",
    );
    // 数据库列表
    public static $dbListConfig = [
        'firstp2p_life',
    ];

}