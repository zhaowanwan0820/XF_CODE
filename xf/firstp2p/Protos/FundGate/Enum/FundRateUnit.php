<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundRateUnit extends AbstractEnum
{
    //费率单位
    const TYPE_BFH = 6;//百分号
    const TYPE_YUAN = 7;//元

    private static $_details = [
        self::TYPE_BFH => "%",
        self::TYPE_YUAN => "元",
    ];

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($type)
    {
        return isset(self::$_details[$type]) ? self::$_details[$type] : "";
    }
}
