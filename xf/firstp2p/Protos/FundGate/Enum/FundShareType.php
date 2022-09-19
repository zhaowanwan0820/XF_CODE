<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundShareType extends AbstractEnum
{
    //费率类别
    const TYPE_BEFORE = "A";//前收费
    const TYPE_AFTER = "B";//后收费

    private static $_details = [
        self::TYPE_BEFORE => "前收费",
        self::TYPE_AFTER => "后收费",
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
