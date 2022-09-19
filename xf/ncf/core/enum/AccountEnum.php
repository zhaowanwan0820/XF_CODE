<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class AccountEnum extends AbstractEnum
{
    //状态
    const STATUS_DEFAULT = 0; //默认值
    const STATUS_OPENED = 1; //已开通
    const STATUS_UNACTIVATED = 2; //未激活

    const MONEY_TYPE_INCR = 1;   //增加余额
    const MONEY_TYPE_REDUCE = 2;   //扣减余额
    const MONEY_TYPE_LOCK = 3;  //冻结金额，增加冻结资金同时减少余额
    const MONEY_TYPE_UNLOCK = 4;   //解冻金额，扣减冻结资金同时增加余额
    const MONEY_TYPE_LOCK_INCR = 5;   //增加冻结金额
    const MONEY_TYPE_LOCK_REDUCE = 6;   //扣减冻结金额

    public static $moneyTypeMap = [
        self::MONEY_TYPE_INCR => '增加余额',
        self::MONEY_TYPE_REDUCE => '扣减余额',
        self::MONEY_TYPE_LOCK => '冻结金额',
        self::MONEY_TYPE_UNLOCK => '解冻金额',
        self::MONEY_TYPE_LOCK_INCR => '增加冻结',
        self::MONEY_TYPE_LOCK_REDUCE => '扣减冻结',
    ];

    public static $statusMap = [
        self::STATUS_DEFAULT => '未开通',
        self::STATUS_OPENED => '已开通',
        self::STATUS_UNACTIVATED => '未激活',
    ];
}
