<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundRateType extends AbstractEnum
{
    //费率类别
    const TYPE_SUBSCRIBE_FRONT = 10010;//认购费前端
    const TYPE_SUBSCRIBE_OTC_FRONT = 10210;//认购费场外前端
    const TYPE_PURCHASE_FRONT = 11010;//日常申购费前端
    const TYPE_PURCHASE_OTC_FRONT = 11210;//日常申购费场外前端
    const TYPE_REDEEM = 12000;//日常赎回费
    const TYPE_REDEEM_OTC = 12200;//日常赎回费场外
    const TYPE_MANAGEMENT_FEE = 15000;//管理费
    const TYPE_CUSTODY_FEE = 16000;//托管费

    private static $_details = [
        self::TYPE_SUBSCRIBE_FRONT => "认购费前端",
        self::TYPE_SUBSCRIBE_OTC_FRONT => "认购费场外前端",
        self::TYPE_PURCHASE_FRONT => "日常申购费前端",
        self::TYPE_PURCHASE_OTC_FRONT => "日常申购费场外前端",
        self::TYPE_REDEEM => "日常赎回费",
        self::TYPE_REDEEM_OTC => "日常赎回费场外",
        self::TYPE_MANAGEMENT_FEE => "管理费",
        self::TYPE_CUSTODY_FEE => "托管费",
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
