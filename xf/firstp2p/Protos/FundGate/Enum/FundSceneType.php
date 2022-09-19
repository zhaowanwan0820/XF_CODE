<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundSceneType extends AbstractEnum
{
    //业务场景类别
    const TYPE_SUBSCRIBE = 1;//认购
    const TYPE_PURCHASE = 2;//申购
    const TYPE_REDEEM = 3;//赎回

    private static $_details = [
        self::TYPE_SUBSCRIBE => "认购",
        self::TYPE_PURCHASE => "申购",
        self::TYPE_REDEEM => "赎回",
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
