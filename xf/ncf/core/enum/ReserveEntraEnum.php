<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ReserveEntraEnum extends AbstractEnum {

    //入口状态
    const STATUS_VALID = 1; //有效
    const STATUS_INVALID = 0; //无效

    public static $statusName = [
        self::STATUS_VALID => '有效',
        self::STATUS_INVALID => '无效',
    ];

    //默认最小预约金额
    const RESERVE_DEFAULT_MIN_AMOUNT = 100;

}
