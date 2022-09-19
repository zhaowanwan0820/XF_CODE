<?php

namespace NCFGroup\Protos\O2O\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CouponGroupEnum extends AbstractEnum {
    // 使用规则
    const OFFLINE_LIMIT_USE = 1;                    // 线下兑换需用户确认
    const OFFLINE_UNLIMIT_USE = 2;                  // 线下兑换无需用户确认
    const ONLINE_GOODS_REPORT = 3;                  // 线上兑换需收货信息-报表
    const ONLINE_GOODS_REALTIME = 4;                // 线上兑换需收货信息-实时
    const ONLINE_COUPON_REPORT = 5;                 // 线上兑换需收券信息-报表
    const ONLINE_COUPON_REALTIME = 6;               // 线上兑换需收券信息-实时
    const ONLINE_COUPON_ATONCE_REALTIME = 7;        // 线上领取即兑换--无需第三方标识（用于网信投资红包）
    const ONLINE_COUPON_ATONCE_REPORT = 8;          // 线上领取即兑换-需第三方标识（用于AA租车，中免等）
    const ONLINE_COUPON_ATONCE_WXLUCKYMONEY = 9;    // 领取即兑换－网信分享红包组
    const ONLINE_COUPON_ATONCE_DAY_SIGNIN = 10;     // 领取即兑换－签到红包
    const ONLINE_COUPON_ATONCE_CUSTOM_COUPON = 11;  // 领取即兑换-定制礼券模板
    const ONLINE_COUPON_ATONCE_GAME = 12;           // 领取即兑换-游戏活动平台
    const ONLINE_COUPON_ATONCE_GAME_CENTER = 13;    // 领取即兑换-游戏中心

    // 触发规则
    const TRIGGER_BID_DEFAULT = 0;                  // 老数据的默认值是触发投资区间
    const TRIGGER_BID_MONEY = 1;                    // 触发投资区间
    const TRIGGER_BID_ANNUALIZED = 2;               // 触发投资年化

    // status 状态
    const STATUS_EFFECT = 1;        // 终审通过
    const STATUS_UNEFFECT = 0;      // 待初审
    const STATUS_LAST_CHECK = 2;    // 待终审
    const STATUS_CHECK_FAILED = 3;  // 审核未通过

    // 全局券组有效期时间
    const GLOBAL_COUPON_GROUP_EFFECT_TIME = 864000;

    // coupon_source 券码来源
    const SOURCE_FROM_PLATFORM = 1;         // 本平台生成
    const SOURCE_FROM_MANUAL_IMPORT = 2;    // 手工导入
    const SOURCE_FROM_PARTNER = 3;          // 合作方

    // trigger_mode触发方式
    const TRIGGER_REGISTER = 1;             // 注册后触发
    const TRIGGER_FIRST_BINDCARD = 2;       // 首次绑卡触发
    const TRIGGER_FIRST_DOBID = 3;          // 首次投资后触发
    const TRIGGER_REPEAT_DOBID = 4;         // 复投触发（第三次及以上投资）
    const TRIGGER_ADMIN_PUSH = 5;           // 后台直推
    const TRIGGER_ROULETTE = 6;             // 大转盘
    const TRIGGER_DAY_FIRST_DOBID = 7;      // 每日首次投资触发
    const TRIGGER_MEDAL = 8;                // 勋章活动奖励
    const TRIGGER_SECOND_DOBID = 9;         // 第二次投资
    const TRIGGER_DAY_FIRST_CHARGE = 10;    // 每日首次充值（废弃）
    const TRIGGER_CHARGE = 11;              // 充值
    const TRIGGER_ONLINE_CHARGE = 19;       // 快捷充值(线上)
    const TRIGGER_OFFLINE_CHARGE = 20;      // 大额充值(线下)
    const TRIGGER_RESEND_COUPON = 12;             // 直推任务
    const TRIGGER_GOLD_FIRST_DOBID = 13;          // 黄金首次购买触发
    const TRIGGER_GOLD_REPEAT_DOBID = 14;         // 黄金复投触发

    const TRIGGER_SUDAI_OPEN = 15;                // 速贷开通
    const TRIGGER_SUDAI_FIRST_DOBID = 16;         // 速贷首次借款触发
    const TRIGGER_SUDAI_REPEAT_DOBID = 17;        // 速贷非首次借款触发
    const TRIGGER_SUDAI_PAYBACK = 18;             // 速贷还款触发
    const TRIGGER_DUOTOU_FIRST_DOBID = 30;
    const TRIGGER_DUOTOU_REPEAT_DOBID = 31;

    const TRIGGER_GAME_AR_BONUS = 32;           // AR红包触发奖励礼券

    const TRIGGER_EXCHANGE_SUPPLIER_STORE = 101;  // 兑券后供应商补贴零售店
    const TRIGGER_EXCHANGE_CHANNEL_STORE = 102;   // 兑券后渠道补贴零售店
    const TRIGGER_EXCHANGE_WX_OWNER = 103;        // 兑券后网信补贴投资人
    const TRIGGER_EXCHANGE_WX_SUPPLIER = 104;     // 兑券后网信补贴供应商
    const TRIGGER_EXCHANGE_WX_CHANNEL= 105;       // 兑券后网信补贴渠道
    const TRIGGER_EXCHANGE_WX_INVITER = 106;      // 兑券后网信补贴邀请人
    const TRIGGER_ACQUIRE_WX_INVITER = 107;       // 领券后网信补贴邀请人
    const TRIGGER_EXCHANGE_WX_STORE = 108;        // 兑券后网信补贴零售店
    const TRIGGER_ACQUIRE_WX_OWNER = 109;         // 领券后网信补贴投资人
    const TRIGGER_ACQUIRE_OWNER_PAYOUT = 110;     // 领券后投资人支出

    // 每日首次投资
    //allowanceType 状态
    const ALLOWANCE_TYPE_MONEY = 1;             // 现金
    const ALLOWANCE_TYPE_BONUS = 2;             // 红包
    const ALLOWANCE_TYPE_LUCKYMONEY = 3;        // 网信红包组（分享红包）
    const ALLOWANCE_TYPE_COUPON = 4;            // 礼券
    const ALLOWANCE_TYPE_DISCOUNT = 5;          // 投资券（投资券）
    const ALLOWANCE_TYPE_NOTE = 6;              // 凭证
    const ALLOWANCE_TYPE_NEY_YEAR_PACKAGE = 7;  // 新年感恩投资券大礼包
    const ALLOWANCE_TYPE_GOLD = 8;              // 黄金
    const ALLOWANCE_TYPE_GAME_CENTER = 9;          // 游戏活动:开箱子,配置的返利是宝箱ID

    //use_time_type 使用时间类型
    const USE_TIME_DAY_LIMIT = 1;           // 动态时间
    const USE_TIME_STATIC = 2;              // 静态时间
    const USE_TIME_DAILY_VALID = 3;         // 领取当日有效

    // 有无服务人
    const SERVICER_OR_NOT = 0;  // 所有
    const SERVICER_FALSE = 1;   // 无服务人
    const SERVICER_TRUE = 2;    // 有服务人

    // 用户类别
    const USER_TYPE_ALL = 0;                // 包含所有
    const USER_TYPE_PERSON = 1;             // 个人用户
    const USER_TYPE_ENTERPRISE = 2;         //企业用户

    // 补贴方式
    const EXCHANGE_SUPPLIER_STORE = 1;  // 兑券后供应商补贴零售店
    const EXCHANGE_CHANNEL_STORE = 2;   // 兑券后渠道补贴零售店
    const EXCHANGE_WX_OWNER = 3;        // 兑券后网信补贴投资人
    const EXCHANGE_WX_SUPPLIER = 4;     // 兑券后网信补贴供应商
    const EXCHANGE_WX_CHANNEL= 5;       // 兑券后网信补贴渠道
    const EXCHANGE_WX_INVITER = 6;      // 兑券后网信补贴邀请人
    const ACQUIRE_WX_INVITER = 7;       // 领券后网信补贴邀请人
    const EXCHANGE_WX_STORE = 8;        // 兑券后网信补贴零售店
    const ACQUIRE_WX_OWNER = 9;         // 领券后网信补贴投资人
    const ACQUIRE_OWNER_PAYOUT = 10;    // 领券后投资人支出

    // 推送方式
    const PUSH_TYPE_PAYOUT = 1;         // 支出方
    const PUSH_TYPE_PAYIN = 2;          // 收入方
    const PUSH_TYPE_WX_OWNER = 3;       // 推送投资人
    const PUSH_TYPE_WX_INVITER = 4;     // 推送邀请人
    const PUSH_TYPE_WX_SERVICER = 5;    // 推送给服务人

    // 限制使用方式
    const RESTRICT_TYPE_DEAL = 1;       // 可用于出借
    const RESTRICT_TYPE_NO_DEAL = 2;    // 不可用于出借

    // 标的要求
    const DEAL_USE_RULES_CATEGORY = 1;  // 产品类别
    const DEAL_USE_RULES_TAGS = 2;      // 标签
    const DEAL_USE_RULES_PROJECTS = 3;  // 所属项目

    // 投资券类型
    const DISCOUNT_TYPE_CASHBACK = 1;   // 返现
    const DISCOUNT_TYPE_RAISE_RATES = 2;// 加息券
    const DISCOUNT_TYPE_GOLD = 3;       // 黄金优惠券
    const DISCOUNT_TYPE_INSURANCE = 4;  // 保险券
    const DISCOUNT_TYPE_LOAN = 5;       // 贷款券

    //投资券组来源
    const SOURCE_ADMIN = 0;
    const SOURCE_CRM = 1;
    const SOURCE_REBATE_GOLD = 2;

    // 投资券发放方式
    const DISCOUNT_GIVE_WITH_INTEREST = 1;  // 随息发放
    const DISCOUNT_GIVE_ONE_TIME = 2;       // 一次性发放

    // 交易的类型区分
    const CONSUME_TYPE_P2P = 1;             // p2p交易
    const CONSUME_TYPE_DUOTOU = 2;          // 智多鑫交易
    const CONSUME_TYPE_DUOTOU_ORDER = 3;    // 智多鑫订单
    const CONSUME_TYPE_GOLD = 4;            // 黄金交易（优长金）
    const CONSUME_TYPE_GOLD_ORDER = 5;      // 黄金订单
    const CONSUME_TYPE_GOLD_CURRENT = 6;    // 活期黄金交易（优金宝）
    const CONSUME_TYPE_RESERVE = 7;         // 随心约
    const CONSUME_TYPE_RECHARGE = 8;        // 充值订单
    const CONSUME_TYPE_SUDAI = 9;           // 速贷
    const CONSUME_TYPE_ZHUANXIANG = 10;     // 专享
    const CONSUME_TYPE_GAME = 11;     // 游戏中心

    // 排行榜业务来源类型
    const RANK_DEAL_TYPE_P2P = 1;               // P2P
    const RANK_DEAL_TYPE_ZHUANXIANG = 2;        // 专享

    // 站点ID规则
    const SITEID_EX = 0;                //不包含
    const SITEID_IN = 1;                //包含

    // 注册时间比较的类型
    const TRIGGER_COMPARE_LESS = 1;
    const TRIGGER_COMPARE_EQ = 2;
    const TRIGGER_COMPARE_MORE = 3;
    const TRIGGER_COMPARE_LESS_EQ = 4;
    const TRIGGER_COMPARE_MORE_EQ = 5;

    // 券组过滤相关key
    const KEY_O2O_TRIGGER_ID = 'o2o:trigger:id:';
    const KEY_O2O_TRIGGER_ACTION = 'o2o:trigger:action:';
    const KEY_O2O_TRIGGER_SITEID = 'o2o:trigger:siteId:';
    const KEY_O2O_TRIGGER_USERGROUPID = 'o2o:trigger:userGroupId:';
    const KEY_O2O_TRIGGER_REFERGROUPID = 'o2o:trigger:referGroupId:';

    // 是否用于合作方推送
    const SEND_BY_PARTNER = 1;          //允许合作方直推
    const SEND_NOT_BY_PARTNER = 0;      //不允许合作方直推

    // 触发后奖励方式
    const TRIGGER_REWARD_REALTIME_POPUP = 1;        // 实时弹出礼券
    const TRIGGER_REWARD_USER_COUPON = 2;           // 直推投资人礼券
    const TRIGGER_REWARD_USER_DISCOUNT = 3;         // 直推投资人投资券
    const TRIGGER_REWARD_INVITER_COUPON = 4;        // 直推邀请人礼券
    const TRIGGER_REWARD_INVITER_DISCOUNT = 5;      // 直推邀请人投资券
    const TRIGGER_REWARD_INVITER_NOTE = 6;          // 直推邀请人凭证
    const TRIGGER_REWARD_USER_NOTE = 7;             // 直推投资人凭证
    const TRIGGER_REWARD_USER_REBATE_COUPON = 8;    // 直推投资人返利礼券
    const TRIGGER_REWARD_INVITER_REBATE_COUPON = 9; // 直推邀请人返利礼券
    const TRIGGER_REWARD_USER_GOLD = 10;            // 实时系统赠金
    const TRIGGER_REWARD_EVENT_POPUP = 11;          // 弹出活动地址
    const TRIGGER_REWARD_SERVICER_COUPON = 12;      // 直推服务人礼券
    const TRIGGER_REWARD_SERVICER_DISCOUNT = 13;    // 直推服务人投资券
    const TRIGGER_REWARD_SERVICER_REBATE_COUPON = 14;// 直推服务人返利礼券

    // 触发规则过滤类型
    const TRIGGER_RULE_DEAL_TAG = 1;            // 标tag
    const TRIGGER_RULE_USER_TAG = 2;            // 投资人旧tag
    const TRIGGER_RULE_USER_REMOTE_TAG = 3;     // 投资人远程tag
    const TRIGGER_RULE_USER_GROUP = 4;          // 投资人用户组
    const TRIGGER_RULE_INVITER_TAG = 5;         // 邀请人旧tag
    const TRIGGER_RULE_INVITER_REMOTE_TAG = 6;  // 邀请人远程tag
    const TRIGGER_RULE_INVITER_GROUP = 7;       // 邀请人用户组
    const TRIGGER_RULE_USER_REGISTER_TIME = 8;  // 投资人注册时间间隔
    const TRIGGER_RULE_USER_REGISTER_TIMESTAMP = 9;  // 投资人注册时间范围(绝对时间)
    const TRIGGER_RULE_BID_DAY_LIMIT = 10;      // 投资天数
    const TRIGGER_RULE_USE_PURPOSE_FIRST = 11;  // 新手券
    const TRIGGER_RULE_GOLD_DAY = 12;           // 标的期限（天数）
    const TRIGGER_RULE_SERVICER_TAG = 13;       // 服务人旧Tag
    const TRIGGER_RULE_SERVICER_REMOTE_TAG = 14;    // 服务人远程Tag
    const TRIGGER_RULE_SERVICER_GROUP = 15;    // 服务人用户组

    // 投资券赠送方式
    const GIVEN_TYPE_SEND_FORBIDDEN = 1;        // 不可用于赠送
    const GIVEN_TYPE_ONLY_SEND = 2;             // 仅可用于赠送
    const GIVEN_TYPE_SEND_USE = 3;              // 可赠送可使用
    const GIVEN_TYPE_SEND_ONLY_TO_FRIENDS = 4;  // 仅可赠送给好友

    // 站点过滤方式
    const SITE_FILTER_VISIABLE_ALL = 1;         // 全部可见
    const SITE_FILTER_VISIABLE_PART = 2;        // 指定站点可见
    const SITE_FILTER_NON_VISIABLE_PART = 3;    // 指定站点不可见

    // 投资券规则偏好类型
    const DISCOUNT_FAVOR_HF_HIGH_MONEY = 1;     // 投资高频高金额
    const DISCOUNT_FAVOR_HF_LOW_MONEY = 2;      // 投资高频低金额
    const DISCOUNT_FAVOR_HF_LONG_PERIOD = 3;    // 投资高频长期限
    const DISCOUNT_FAVOR_HF_SHORT_PERIOD = 4;   // 投资高频短期限
    const DISCOUNT_FAVOR_CASH_BALANCE = 5;      // 现金余额
    const DISCOUNT_FAVOR_OPTIMAL = 6;           // 最优策略
    const DISCOUNT_FAVOR_SUPER = 7;             // 超级优惠策略（低额策略下的金额和短期策略下的期限）
    const DISCOUNT_FAVOR_HIGH_POTENTIAL = 8;    // 高潜力用户策略

    // 黄金优惠券配置二级筛选
    const GOLD_BID_WEIGHT = 1;
    const GOLD_BID_ANNUALIZED = 2;

    // 触发的业务类型
    const TRIGGER_TYPE_P2P = 1;     // P2P业务
    const TRIGGER_TYPE_DUOTOU = 2;  // 智多鑫业务
    const TRIGGER_TYPE_GOLD = 3;    // 黄金业务
    const TRIGGER_TYPE_RESERVE = 4; // 随心约业务
    const TRIGGER_TYPE_SUDAI = 5;   // 速贷业务
    const TRIGGER_TYPE_ZHUANXIANG = 6;//专享业务

    // 特殊奖励规则
    const SPECIAL_REWARD_RULE_REWARD_SENDER  = 1;   //被赠送人用后直推赠送人黄金券

    //券组使用意图
    const USE_PURPOSE_NULL = 0; //默认
    const USE_PURPOSE_FIRST = 1; //新手券

    //券组使用意图
    public static $USE_PURPOSE = array(
        self::USE_PURPOSE_NULL => '无',
        self::USE_PURPOSE_FIRST => '新手券'
    );

    //券组来源
    public static $SOURCE = array(
        self::SOURCE_ADMIN => '后台创建',
        self::SOURCE_CRM => 'crm',
        self::SOURCE_REBATE_GOLD => '实时触发黄金',
    );

    public static $REWARD_TYPES = array(
        self::SPECIAL_REWARD_RULE_REWARD_SENDER => '被赠送人用后直推赠送人黄金券'
    );

    public static $TRIGGER_TYPES = array(
        self::TRIGGER_TYPE_P2P => 'P2P',
        self::TRIGGER_TYPE_DUOTOU => '智多鑫',
        self::TRIGGER_TYPE_GOLD => '黄金',
        self::TRIGGER_TYPE_RESERVE => '随心约',
        self::TRIGGER_TYPE_SUDAI   => '速贷',
    );

    public static $GOLD_BID_TYPES = array(
        self::GOLD_BID_WEIGHT => '买金克重',
        self::GOLD_BID_ANNUALIZED => '年化成交金额'
    );

    //黄金触发规则类型
    public static $GOLD_TRIGGER_RULE_TYPES = array(
        self::TRIGGER_RULE_DEAL_TAG => '标Tag',
        self::TRIGGER_RULE_USER_GROUP => '投资人所属会员组',
        self::TRIGGER_RULE_INVITER_GROUP => '邀请人所属会员组',
    );
    //黄金触发规则类型(实时系统赠金)
    public static $GOLD_REBATE_RULE_TYPES = array(
        self::TRIGGER_RULE_DEAL_TAG => '标Tag',
        self::TRIGGER_RULE_USER_GROUP => '投资人所属会员组',
        self::TRIGGER_RULE_INVITER_GROUP => '邀请人所属会员组',
        self::TRIGGER_RULE_USE_PURPOSE_FIRST => '屏蔽新手券',
        self::TRIGGER_RULE_GOLD_DAY => '标的期限（天数）',
    );
    // 黄金触发类型
    public static $GOLD_TRIGGER_MODES = array(
        self::TRIGGER_GOLD_FIRST_DOBID => '首次买金',
        self::TRIGGER_GOLD_REPEAT_DOBID => '第二次及以上买金'
    );

    public static $SUDAI_TRIGGER_RULE_TYPES = array(
        self::TRIGGER_RULE_USER_GROUP => '借款人所属会员组',
        #self::TRIGGER_RULE_INVITER_GROUP => '邀请人所属会员组'
    );

    // 速贷触发类型
    public static $SUDAI_TRIGGER_MODES = array(
        self::TRIGGER_SUDAI_OPEN => '开通速贷',
        self::TRIGGER_SUDAI_FIRST_DOBID => '首次借款',
        self::TRIGGER_SUDAI_REPEAT_DOBID => '非首次借款',
        self::TRIGGER_SUDAI_PAYBACK => '主动还款',
    );

    // 交易类型
    public static $CONSUME_TYPES = array(
        self::CONSUME_TYPE_P2P => 'p2p交易',
        self::CONSUME_TYPE_DUOTOU => '智多鑫交易',
        self::CONSUME_TYPE_DUOTOU_ORDER => '智多鑫订单',
        self::CONSUME_TYPE_GOLD => '黄金交易',
        self::CONSUME_TYPE_GOLD_ORDER => '黄金订单',
        self::CONSUME_TYPE_GOLD_CURRENT => '活期黄金交易',
        self::CONSUME_TYPE_RESERVE => '随心约',
        self::CONSUME_TYPE_RECHARGE => '充值订单',
        self::CONSUME_TYPE_SUDAI => '速贷',
        self::CONSUME_TYPE_ZHUANXIANG => '专享',
    );

    // 投资券规则偏好类型
    public static $DISCOUNT_FAVORS = array(
        self::DISCOUNT_FAVOR_HF_HIGH_MONEY => '投资高频高金额',
        self::DISCOUNT_FAVOR_HF_LOW_MONEY => '投资高频低金额',
        self::DISCOUNT_FAVOR_HF_LONG_PERIOD => '投资高频长期限',
        self::DISCOUNT_FAVOR_HF_SHORT_PERIOD => '投资高频短期限',
        self::DISCOUNT_FAVOR_CASH_BALANCE => '现金余额',
        self::DISCOUNT_FAVOR_OPTIMAL => '最优策略',
        self::DISCOUNT_FAVOR_SUPER => '超级策略',
        self::DISCOUNT_FAVOR_HIGH_POTENTIAL => '高潜力用户策略'
    );

    // 站点过滤方式
    public static $SITE_FILTER_TYPES = array(
        self::SITE_FILTER_VISIABLE_ALL => '全部可见',
        self::SITE_FILTER_VISIABLE_PART => '指定站点可见',
        self::SITE_FILTER_NON_VISIABLE_PART => '指定站点不可见'
    );

    // 投资券赠送方式
    public static $GIVEN_TYPES = array(
        self::GIVEN_TYPE_SEND_FORBIDDEN => '不可用于赠送',
        self::GIVEN_TYPE_ONLY_SEND => '仅可用于赠送',
        self::GIVEN_TYPE_SEND_USE => '可赠送可使用',
        self::GIVEN_TYPE_SEND_ONLY_TO_FRIENDS => '仅可赠送给好友'
    );

    // 触发规则类型
    public static $TRIGGER_RULE_TYPES = array(
        self::TRIGGER_RULE_DEAL_TAG => '标Tag',
        self::TRIGGER_RULE_USER_TAG => '投资人旧Tag',
        self::TRIGGER_RULE_USER_REMOTE_TAG => '投资人新Tag',
        self::TRIGGER_RULE_USER_GROUP => '投资人所属会员组',
        self::TRIGGER_RULE_INVITER_TAG => '邀请人旧Tag',
        self::TRIGGER_RULE_INVITER_REMOTE_TAG => '邀请人新Tag',
        self::TRIGGER_RULE_INVITER_GROUP => '邀请人所属会员组',
        self::TRIGGER_RULE_SERVICER_TAG => '服务人旧Tag',
        self::TRIGGER_RULE_SERVICER_REMOTE_TAG => '服务人新Tag',
        self::TRIGGER_RULE_SERVICER_GROUP => '服务人所属会员组',
        self::TRIGGER_RULE_USER_REGISTER_TIME => '投资人注册时间间隔',
        self::TRIGGER_RULE_USER_REGISTER_TIMESTAMP => '投资人注册时间范围',
        self::TRIGGER_RULE_BID_DAY_LIMIT => '投资天数'
    );

    // 无服务人对应的触发规则类型
    public static $TRIGGER_RULE_TYPES_SPECIAL = array(
        self::TRIGGER_RULE_DEAL_TAG => '标Tag',
        self::TRIGGER_RULE_USER_TAG => '投资人旧Tag',
        self::TRIGGER_RULE_USER_REMOTE_TAG => '投资人新Tag',
        self::TRIGGER_RULE_USER_GROUP => '投资人所属会员组',
        self::TRIGGER_RULE_INVITER_TAG => '邀请人旧Tag',
        self::TRIGGER_RULE_INVITER_REMOTE_TAG => '邀请人新Tag',
        self::TRIGGER_RULE_INVITER_GROUP => '邀请人所属会员组',
        self::TRIGGER_RULE_USER_REGISTER_TIME => '投资人注册时间间隔',
        self::TRIGGER_RULE_USER_REGISTER_TIMESTAMP => '投资人注册时间范围',
        self::TRIGGER_RULE_BID_DAY_LIMIT => '投资天数'
    );

    // 触发奖励类型
    public static $TRIGGER_REWARD_TYPES = array(
        self::TRIGGER_REWARD_REALTIME_POPUP => '实时弹出礼券',
        self::TRIGGER_REWARD_EVENT_POPUP => '弹出活动',
        self::TRIGGER_REWARD_USER_COUPON => '直推投资人礼券',
        self::TRIGGER_REWARD_USER_DISCOUNT => '直推投资人投资券',
        self::TRIGGER_REWARD_INVITER_COUPON => '直推邀请人礼券',
        self::TRIGGER_REWARD_INVITER_DISCOUNT => '直推邀请人投资券',
        self::TRIGGER_REWARD_INVITER_NOTE => '直推邀请人凭证',
        self::TRIGGER_REWARD_USER_NOTE => '直推投资人凭证',
        self::TRIGGER_REWARD_USER_REBATE_COUPON=> '直推投资人返利礼券',
        self::TRIGGER_REWARD_INVITER_REBATE_COUPON => '直推邀请人返利礼券',
        self::TRIGGER_REWARD_SERVICER_COUPON => '直推服务人礼券',
        self::TRIGGER_REWARD_SERVICER_DISCOUNT => '直推服务人投资券',
        self::TRIGGER_REWARD_SERVICER_REBATE_COUPON => '直推服务人返利礼券',
    );

    // 无服务人对应的触发奖励类型
    public static $TRIGGER_REWARD_TYPES_SPECIAL = array(
        self::TRIGGER_REWARD_REALTIME_POPUP => '实时弹出礼券',
        self::TRIGGER_REWARD_EVENT_POPUP => '弹出活动',
        self::TRIGGER_REWARD_USER_COUPON => '直推投资人礼券',
        self::TRIGGER_REWARD_USER_DISCOUNT => '直推投资人投资券',
        self::TRIGGER_REWARD_INVITER_COUPON => '直推邀请人礼券',
        self::TRIGGER_REWARD_INVITER_DISCOUNT => '直推邀请人投资券',
        self::TRIGGER_REWARD_INVITER_NOTE => '直推邀请人凭证',
        self::TRIGGER_REWARD_USER_NOTE => '直推投资人凭证',
        self::TRIGGER_REWARD_USER_REBATE_COUPON=> '直推投资人返利礼券',
        self::TRIGGER_REWARD_INVITER_REBATE_COUPON => '直推邀请人返利礼券',
    );

    //触发奖励类型(黄金)
    public static $GOLD_TRIGGER_REWARD_TYPES = array(
        self::TRIGGER_REWARD_REALTIME_POPUP => '实时弹出礼券',
        self::TRIGGER_REWARD_USER_COUPON => '直推投资人礼券',
        self::TRIGGER_REWARD_USER_DISCOUNT => '直推投资人投资券',
        self::TRIGGER_REWARD_INVITER_COUPON => '直推邀请人礼券',
        self::TRIGGER_REWARD_INVITER_DISCOUNT => '直推邀请人投资券',
    );

    // 黄金触发奖励类型(实时系统赠金)
    public static $GOLD_REBATE_REWARD_TYPES = array(
        self::TRIGGER_REWARD_REALTIME_POPUP => '实时弹出礼券',
        self::TRIGGER_REWARD_USER_COUPON => '直推投资人礼券',
        self::TRIGGER_REWARD_USER_DISCOUNT => '直推投资人投资券',
        self::TRIGGER_REWARD_INVITER_COUPON => '直推邀请人礼券',
        self::TRIGGER_REWARD_INVITER_DISCOUNT => '直推邀请人投资券',
        self::TRIGGER_REWARD_USER_GOLD => '实时系统赠金',
    );

    // o2ocrm获取提醒列表类型
    const NOTICE_COUNT = 1;             //库存数量提醒
    const NOTICE_EXPIRE = 2;            //过期时间提醒
    const NOTICE_ALL = 0;               //全部提醒

    const GAME_PRIZE_COUPON = 1;        //礼券
    const GAME_PRIZE_DISCOUNT = 2;      //投资券

    const ROLE_TYPE_USER = 1;           //投资用户
    const ROLE_TYPE_INVITER = 2;         //邀请人
    const ROLE_TYPE_SERVICER = 3;        //服务人

    public static $RESTRICT_TYPES = array(
        self::RESTRICT_TYPE_DEAL => '可用于出借',
        self::RESTRICT_TYPE_NO_DEAL => '不可用于出借'
    );

    // 投资券关于标的规则过滤
    public static $DEAL_USE_RULES = array(
        self::DEAL_USE_RULES_CATEGORY => '产品类别',
        self::DEAL_USE_RULES_TAGS => '标签',
        self::DEAL_USE_RULES_PROJECTS => '所属项目'
    );

    // 投资券返利类型
    public static $DISCOUNT_TYPES = array(
        self::DISCOUNT_TYPE_CASHBACK => '返现券',
        self::DISCOUNT_TYPE_RAISE_RATES => '加息券',
        self::DISCOUNT_TYPE_GOLD => '黄金券',
    );

    // 投资券返利类型
    public static $DISCOUNT_TYPES_WITHOUT_GOLD = array(
        self::DISCOUNT_TYPE_CASHBACK => '返现券',
        self::DISCOUNT_TYPE_RAISE_RATES => '加息券',
    );

    // 投资券发放方式
    public static $DISCOUNT_GIVE_TYPES = array(
        self::DISCOUNT_GIVE_WITH_INTEREST => '随息发放',
        self::DISCOUNT_GIVE_ONE_TIME => '一次性发放'
    );

    // 礼券返利方式
    public static $ALLOWANCE_MODES = array(
        self::EXCHANGE_SUPPLIER_STORE => '兑券后供应商补贴零售店',
        self::EXCHANGE_CHANNEL_STORE => '兑券后渠道补贴零售店',
        self::EXCHANGE_WX_OWNER => '兑券后网信补贴投资人',
        self::EXCHANGE_WX_SUPPLIER => '兑券后网信补贴供应商',
        self::EXCHANGE_WX_CHANNEL => '兑券后网信补贴渠道',
        self::EXCHANGE_WX_INVITER => '兑券后网信补贴邀请人',
        self::ACQUIRE_WX_INVITER => '领券后网信补贴邀请人',
        self::EXCHANGE_WX_STORE => '兑券后网信补贴零售店',
        self::ACQUIRE_WX_OWNER => '领券后网信补贴投资人',
    );
    public static $TRIGGER_PUSH_TYPES = array(
        self::PUSH_TYPE_WX_OWNER => '推送给投资人',
        self::PUSH_TYPE_WX_INVITER => '推送给邀请人',
        self::PUSH_TYPE_WX_SERVICER => '推送给服务人',
    );
    // 无服务人的消息推送类型
    public static $TRIGGER_PUSH_TYPES_SPECIAL = array(
        self::PUSH_TYPE_WX_OWNER => '推送给投资人',
        self::PUSH_TYPE_WX_INVITER => '推送给邀请人',
    );

    public static $PUSH_TYPES = array(
        self::PUSH_TYPE_PAYOUT => '支出方',
        self::PUSH_TYPE_PAYIN => '收入方'
    );
    public static $STATUS = array(
        self::STATUS_UNEFFECT => '无效',
        self::STATUS_EFFECT => '有效',
    );

    // 线上表单的使用规则
    public static $ONLINE_FORM_RULES = array(
        self::ONLINE_COUPON_REPORT,
        self::ONLINE_COUPON_REALTIME,
        self::ONLINE_COUPON_ATONCE_REPORT
    );
    // 对于线下兑换需要地址的情况
    public static $OFFLINE_USE_RULES = array(
        self::OFFLINE_UNLIMIT_USE
    );
    // 需要第三方推送
    public static $PARTNER_PUSH_USE_RULES = array(
        self::ONLINE_COUPON_REALTIME,
        self::ONLINE_COUPON_ATONCE_REPORT
    );

    // 线上兑换需要表单
    public static $ONLINE_FORM_USE_RULES = array(
        self::ONLINE_GOODS_REPORT,
        self::ONLINE_GOODS_REALTIME,
        self::ONLINE_COUPON_REPORT,
        self::ONLINE_COUPON_REALTIME
    );

    // 线上兑换需要生成订单
    public static $ONLINE_ORDER_USE_RULES = array(
        self::ONLINE_GOODS_REPORT,
        self::ONLINE_GOODS_REALTIME,
        self::ONLINE_COUPON_REPORT,
        self::ONLINE_COUPON_REALTIME,
        self::ONLINE_COUPON_ATONCE_REPORT
    );

    // 线上领取即兑换的使用类型
    public static $ONLINE_ATONCE_USE_RULES = array(
        self::ONLINE_COUPON_ATONCE_REALTIME,
        self::ONLINE_COUPON_ATONCE_REPORT,
        self::ONLINE_COUPON_ATONCE_WXLUCKYMONEY,
        self::ONLINE_COUPON_ATONCE_DAY_SIGNIN,
        self::ONLINE_COUPON_ATONCE_CUSTOM_COUPON,
        self::ONLINE_COUPON_ATONCE_GAME,
        self::ONLINE_COUPON_ATONCE_GAME_CENTER,
    );

    public static $USE_RULES = array(
        self::OFFLINE_UNLIMIT_USE => '线下兑换无需用户确认',
        self::ONLINE_GOODS_REPORT => '线上兑换需收货信息-报表',
        self::ONLINE_GOODS_REALTIME => '线上兑换需收货信息-实时',
        self::ONLINE_COUPON_REPORT => '线上兑换需收券信息-报表',
        self::ONLINE_COUPON_REALTIME => '线上兑换需收券信息-实时',
        self::ONLINE_COUPON_ATONCE_REALTIME => '领取即兑换-无需第三方标识',
        self::ONLINE_COUPON_ATONCE_REPORT => '领取即兑换-需第三方标识',
        self::ONLINE_COUPON_ATONCE_WXLUCKYMONEY => '领取即兑换－网信分享红包组',
        self::ONLINE_COUPON_ATONCE_DAY_SIGNIN => '领取即兑换－签到红包',
        self::ONLINE_COUPON_ATONCE_CUSTOM_COUPON => '领取即兑换-定制礼券模板',
        self::ONLINE_COUPON_ATONCE_GAME => '领取即兑换-游戏活动平台',
        self::ONLINE_COUPON_ATONCE_GAME_CENTER => '领取即兑换-游戏中心',
    );
    public static $COUPON_SOURCE = array(
        self::SOURCE_FROM_PLATFORM => '领取时由本平台生成',
        self::SOURCE_FROM_MANUAL_IMPORT => '手动批量导入',
        self::SOURCE_FROM_PARTNER => '领取时由合作方下发'
    );

    // 投标相关触发
    public static $TRIGGER_DEAL_MODES = array(
        self::TRIGGER_FIRST_DOBID,               // 首投
        self::TRIGGER_SECOND_DOBID,              // 第二次投资
        self::TRIGGER_REPEAT_DOBID,              // 第三次及以上投资
        self::TRIGGER_DAY_FIRST_DOBID,           // 每日首投
        self::TRIGGER_GOLD_FIRST_DOBID,          // 黄金首次购买
        self::TRIGGER_GOLD_REPEAT_DOBID,         // 黄金第二次及以上购买
        self::TRIGGER_DUOTOU_FIRST_DOBID,        // 多投首次投资
        self::TRIGGER_DUOTOU_REPEAT_DOBID,       // 多投第二次及以上投资
    );

    public static $TRIGGER_MODE = array(
        self::TRIGGER_REGISTER => '注册',
        self::TRIGGER_FIRST_BINDCARD => '首次绑卡',
        // self::TRIGGER_DAY_FIRST_CHARGE => '每日首次充值', (废弃下线)
        self::TRIGGER_CHARGE => '充值',
        self::TRIGGER_ONLINE_CHARGE => '快捷充值(线上)',
        self::TRIGGER_OFFLINE_CHARGE => '大额充值(线下)',
        self::TRIGGER_FIRST_DOBID => '首次投资',
        self::TRIGGER_SECOND_DOBID => '第二次投资',
        self::TRIGGER_REPEAT_DOBID => '第三次及以上投资',
        self::TRIGGER_DAY_FIRST_DOBID => '每日首次投资',
        self::TRIGGER_ADMIN_PUSH => '后台直推',
        self::TRIGGER_DUOTOU_REPEAT_DOBID => '多投复投',
        self::TRIGGER_GOLD_FIRST_DOBID => '首次买金',
        self::TRIGGER_GOLD_REPEAT_DOBID => '第二次及以上买金',
        self::TRIGGER_SUDAI_OPEN => '开通速贷',
        self::TRIGGER_SUDAI_FIRST_DOBID => '首次借款',
        self::TRIGGER_SUDAI_REPEAT_DOBID => '非首次借款',
        self::TRIGGER_SUDAI_PAYBACK => '主动还款',
    );

    public static $TRIGGER_MODE_FOR_ADMIN = array(
        self::TRIGGER_REGISTER => '注册',
        self::TRIGGER_FIRST_BINDCARD => '绑卡',
        self::TRIGGER_FIRST_DOBID => '首投',
        self::TRIGGER_REPEAT_DOBID => '第三次及以上投资',
        self::TRIGGER_ADMIN_PUSH => '赠品',
        self::TRIGGER_ROULETTE => '大转盘',
        self::TRIGGER_DAY_FIRST_DOBID => '每日首次投资',
        self::TRIGGER_MEDAL => '勋章活动奖励',
        self::TRIGGER_SECOND_DOBID => '第二次投资',
        // self::TRIGGER_DAY_FIRST_CHARGE => '每日首次充值', (废弃下线)
        self::TRIGGER_CHARGE => '充值',
        self::TRIGGER_RESEND_COUPON => '直推任务',
        self::TRIGGER_DUOTOU_REPEAT_DOBID => '智多鑫复投',
        self::TRIGGER_GOLD_FIRST_DOBID => '首次买金',
        self::TRIGGER_GOLD_REPEAT_DOBID => '第二次及以上买金',
        self::TRIGGER_SUDAI_OPEN => '开通速贷',
        self::TRIGGER_SUDAI_FIRST_DOBID => '首次借款',
        self::TRIGGER_SUDAI_REPEAT_DOBID => '非首次借款',
        self::TRIGGER_SUDAI_PAYBACK => '主动还款',
    );

    // 投资券返利方式
    public static $DISCOUNT_ALLOWANCE_TYPE = array(
        self::ALLOWANCE_TYPE_MONEY => '现金',
        self::ALLOWANCE_TYPE_BONUS => '红包',
        self::ALLOWANCE_TYPE_GOLD => '黄金'
    );

    // 投资券后台返利方式
    public static $DISCOUNT_ALLOWANCE_TYPE_FOR_ADMIN = array(
        self::ALLOWANCE_TYPE_BONUS => '红包',
    );

    // 礼券的返利类型
    public static $ALLOWANCE_TYPE = array(
        self::ALLOWANCE_TYPE_MONEY => '现金',
        self::ALLOWANCE_TYPE_BONUS => '红包',
        self::ALLOWANCE_TYPE_LUCKYMONEY => '分享红包',
        self::ALLOWANCE_TYPE_COUPON => '礼券',
        self::ALLOWANCE_TYPE_DISCOUNT => '优惠券',
        self::ALLOWANCE_TYPE_GAME_CENTER => '游戏中心',
    );

    // 时间的使用方式
    public static $USE_TIME_TYPE = array(
        self::USE_TIME_DAY_LIMIT => '动态时间',
        self::USE_TIME_STATIC => '静态时间',
    );

    // 有无服务人
    public static $IS_SERVICER = array(
        self::SERVICER_OR_NOT => '所有',
        self::SERVICER_FALSE => '无服务人',
        self::SERVICER_TRUE => '有服务人'
    );

    // 用户类别
    public static $USER_TYPE = array(
        self::USER_TYPE_ALL => '所有',
        self::USER_TYPE_PERSON => '个人',
        self::USER_TYPE_ENTERPRISE => '企业'
    );

    // 站点包含方式
    public static $SITE_MODES = array(
        self::SITEID_IN => '包含',
        self::SITEID_EX => '不包含',
    );

    // 触发比较类型
    public static $TRIGGER_COMPARE_MODES = array(
        self::TRIGGER_COMPARE_LESS => '小于',
        self::TRIGGER_COMPARE_EQ => '等于',
        self::TRIGGER_COMPARE_MORE => '大于',
        self::TRIGGER_COMPARE_LESS_EQ => '小于等于',
        self::TRIGGER_COMPARE_MORE_EQ => '大于等于'
    );

    // 充值相关触发方式
    public static $CHARGE_TRIGGER = array(
        self::TRIGGER_ONLINE_CHARGE,
        self::TRIGGER_OFFLINE_CHARGE
        // self::TRIGGER_DAY_FIRST_CHARGE,
        // self::TRIGGER_CHARGE
    );

    //p2p触发时远程tag过滤名单，不传递给o2o
    public static $BLACK_TAG = array(
        "code",
        "bmonth",
        "money",
        "today_deal_count",
        "attr",
        "deal_money",
        "7days_deal_count",
        "first_deal_invitee_today",
        "left_bonus",
        "invitee_deal_money_by_year",
        "login_time",
        "deal_count",
        "mobile_type",
        "deal_money_by_year",
        "user_id",
        "invitee_count",
        "invitee_deal_money",
        "name",
        "mobile",
        "idno",
        "invitee_set",
        "best_friends"
    );
}
