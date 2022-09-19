<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CouponEnum extends AbstractEnum
{
    // 券码状态
    const STATUS_UNCLAIMED = 0;     // 未领取
    const STATUS_UNUSED = 1;        // 已领取未使用
    const STATUS_USED = 2;          // 已使用
    const STATUS_EXPIRED = 3;       // 已过期
    const STATUS_WAIT_CONFIRM = 4;  // 待兑换确认
    const STATUS_ORDER_CONFIRM = 5; // 对于即时兑换，待确定订单状态
    const STATUS_LOCKED = 6;        // 已锁定，用于投资券转增的时候，用户还未注册的情况

    const TYPE = 'ncfph';
    const TYPE_DUOTOU = 'duotou';
    const SHORT_ALIAS_DEFAULT = 'F00000';
}
