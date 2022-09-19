<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MoneyEventType extends AbstractEnum
{
    /** 事件类型 */
    const EVENT_TYPE_LOCK   = 1;//冻结资金
    const EVENT_TYPE_UNLOCK = 2;//解冻资金
    const EVENT_TYPE_REDEEM = 3;//加钱

    private static $_details = array(
        self::EVENT_TYPE_LOCK => '冻结资金',
        self::EVENT_TYPE_UNLOCK => '解冻资金',
        self::EVENT_TYPE_REDEEM => '加钱',
    );

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($status)
    {
        return isset(self::$_details[$status]) ? self::$_details[$status] : "";
    }
}
