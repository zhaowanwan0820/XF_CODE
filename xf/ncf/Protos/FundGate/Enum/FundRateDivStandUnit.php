<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundRateDivStandUnit extends AbstractEnum
{
    //费率划分标准单位类别
    const TYPE_YEAR = 1;
    const TYPE_MONTH = 2;
    const TYPE_DAY = 3;
    const TYPE_WAN_YUAN = 4;
    const TYPE_WAN_FEN = 5;

    private static $_details = [
        self::TYPE_YEAR => "年",
        self::TYPE_MONTH => "月",
        self::TYPE_DAY => "天",
        self::TYPE_WAN_YUAN => "万元",
        self::TYPE_WAN_FEN => "万份",
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
