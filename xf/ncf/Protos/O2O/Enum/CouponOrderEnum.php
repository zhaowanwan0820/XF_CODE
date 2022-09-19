<?php
namespace NCFGroup\Protos\O2O\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CouponOrderEnum extends AbstractEnum {
    // 订单状态
    const INVALID_STATUS = 0;   // 无效
    const WAITING_STATUS = 1;   // 等待确认
    const EFFECT_STATUS= 2;     // 有效
    // 通知状态
    const STATUS_UNNOTIFY = 0;  // 未通知结果
    const STATUS_NOTIFIED = 1;  // 已通知结果
    // 订单状态
    public static $ORDER_STATUS = array(
        self::INVALID_STATUS => '无效',
        self::WAITING_STATUS => '等待确认',
        self::EFFECT_STATUS => '有效',
    );
    // 通知状态
    public static $STATUS = array(
        self::STATUS_UNNOTIFY => '未通知',
        self::STATUS_NOTIFIED => '已通知',
    );
}
