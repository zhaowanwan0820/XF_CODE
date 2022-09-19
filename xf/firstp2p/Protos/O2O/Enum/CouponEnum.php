<?php
namespace NCFGroup\Protos\O2O\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CouponEnum extends AbstractEnum {
    // 券码状态
    const STATUS_UNCLAIMED = 0;     // 未领取
    const STATUS_UNUSED = 1;        // 已领取未使用
    const STATUS_USED = 2;          // 已使用
    const STATUS_EXPIRED = 3;       // 已过期
    const STATUS_WAIT_CONFIRM = 4;  // 待兑换确认
    const STATUS_ORDER_CONFIRM = 5; // 对于即时兑换，待确定订单状态
    const STATUS_LOCKED = 6;        // 已锁定，用于投资券转增的时候，用户还未注册的情况

    // 券码可用状态:新增可用状态筛选，1-可用(包含待使用和待兑换确认);2-不可用(包含已使用和已过期)
    const USE_STATUS_CAN_USE = 1;
    const USE_STATUS_CANNOT_USE = 2;

    // 券码未领取相关状态
    const ACQUIRE_PICKING = 0;
    const ACQUIRE_PICKED = 1;
    const ACQUIRE_EXPIRED = 2;

    const REQUEST_SUCCESS_LIST = 1;
    const REQUEST_WAITING = 2;
    const REQUEST_SUCCESS_EMPTY = 3;

    public static $UNPICK_STATUS = array(
        self::ACQUIRE_PICKING => '待领取',
        self::ACQUIRE_PICKED => '已领取',
        self::ACQUIRE_EXPIRED => '已过期'
    );

    // 券码来源
    const COUPON_FROM_EVENT_ROULETTE = 1; // 活动奖励（大转盘）
    // 券码状态
    public static $STATUS = array(
        self::STATUS_UNCLAIMED => '未领取',
        self::STATUS_UNUSED => '已领取未使用',
        self::STATUS_USED => '已使用',
        self::STATUS_EXPIRED => '已过期',
        self::STATUS_WAIT_CONFIRM => '待兑换确认',
        self::STATUS_ORDER_CONFIRM => '待确定订单状态'
    );
    // 投资的券码状态
    public static $DISCOUNT_STATUS = array(
        self::STATUS_UNUSED => '已领取未使用',
        self::STATUS_USED => '已使用',
        self::STATUS_EXPIRED => '已过期',
        self::STATUS_WAIT_CONFIRM => '待兑换确认',
        self::STATUS_LOCKED => '已锁定'
    );
    // 券码来源
    public static $COUPON_FROM = array(
        self::COUPON_FROM_EVENT_ROULETTE => '活动奖励'
    );
}
