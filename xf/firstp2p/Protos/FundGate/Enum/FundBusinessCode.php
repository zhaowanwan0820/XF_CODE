<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundBusinessCode extends AbstractEnum
{
    //业务代码类别（面向宜投接口业务）
    const TYPE_SUBSCRIBE = "020";//认购
    const TYPE_PURCHASE = "022";//申购
    const TYPE_REDEEM = "024";//赎回

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
