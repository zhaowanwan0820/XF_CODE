<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CouponGroupEnum extends AbstractEnum {

    // 交易的类型区分
    const CONSUME_TYPE_P2P = 1;             // p2p交易
    const CONSUME_TYPE_DUOTOU = 2;          // 智多鑫交易
    const CONSUME_TYPE_DUOTOU_ORDER = 3;    // 智多鑫订单
    const CONSUME_TYPE_RESERVE = 7;         // 随心约
    const CONSUME_TYPE_RECHARGE = 8;        // 充值订单
    const CONSUME_TYPE_SUDAI = 9;           // 速贷

    // trigger_mode触发方式
    const TRIGGER_DUOTOU_FIRST_DOBID = 30;
    const TRIGGER_DUOTOU_REPEAT_DOBID = 31;
    const TRIGGER_FIRST_DOBID = 3;          // 首次投资后触发
    const TRIGGER_REPEAT_DOBID = 4;         // 复投触发（第三次及以上投资）

    const TRIGGER_ONLINE_CHARGE = 19;       // 快捷充值(线上)
    const TRIGGER_OFFLINE_CHARGE = 20;      // 大额充值(线下)

    // 使用规则
    // 线上兑换需要表单
    const OFFLINE_LIMIT_USE = 1;                    // 线下兑换需用户确认
    const OFFLINE_UNLIMIT_USE = 2;                  // 线下兑换无需用户确认
    const ONLINE_GOODS_REPORT = 3;                  // 线上兑换需收货信息-报表
    const ONLINE_GOODS_REALTIME = 4;                // 线上兑换需收货信息-实时
    const ONLINE_COUPON_REPORT = 5;                 // 线上兑换需收券信息-报表
    const ONLINE_COUPON_REALTIME = 6;               // 线上兑换需收券信息-实时
    const ONLINE_COUPON_ATONCE_REPORT = 8;          // 线上领取即兑换-需第三方标识（用于AA租车，中免等）
    const ONLINE_COUPON_ATONCE_WXLUCKYMONEY = 9;    // 领取即兑换－网信分享红包组
    const ONLINE_COUPON_ATONCE_GAME = 12;           // 领取即兑换-游戏活动平台
    const ONLINE_COUPON_ATONCE_GAME_CENTER = 13;    // 领取即兑换-游戏中心

    // 排行榜业务来源类型
    const RANK_DEAL_TYPE_P2P = 1;               // P2P
    const RANK_DEAL_TYPE_ZHUANXIANG = 2;        // 专享

    public static $ONLINE_FORM_USE_RULES = array(
        self::ONLINE_GOODS_REPORT,
        self::ONLINE_GOODS_REALTIME,
        self::ONLINE_COUPON_REPORT,
        self::ONLINE_COUPON_REALTIME
    );

    // 线上表单的使用规则
    public static $ONLINE_FORM_RULES = array(
        self::ONLINE_COUPON_REPORT,
        self::ONLINE_COUPON_REALTIME,
        self::ONLINE_COUPON_ATONCE_REPORT
    );

    // 线上兑换需要生成订单
    public static $ONLINE_ORDER_USE_RULES = array(
        self::ONLINE_GOODS_REPORT,
        self::ONLINE_GOODS_REALTIME,
        self::ONLINE_COUPON_REPORT,
        self::ONLINE_COUPON_REALTIME,
        self::ONLINE_COUPON_ATONCE_REPORT
    );
}

