<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MoneyStatus extends AbstractEnum
{
    /** 资金状态 */
    const STATUS_INIT = 1;//初始
    const STATUS_DONE = 2;//完成

    private static $_details = array(
        self::STATUS_INIT => "初始",
        self::STATUS_DONE => "完成",
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
