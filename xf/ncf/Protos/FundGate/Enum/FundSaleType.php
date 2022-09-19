<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundSaleType extends AbstractEnum
{
       // 买卖方式
    const SALE_TYPE_WHATEVER = 1; // 随买随卖
    const SALE_TYPE_QUARTERLY_DIVIDENDS = 2; //按季分红  20151105新增
    const SALE_TYPE_ONE_TIME_DEBT_SERVICE = 3;//一次性还本付息  20151105新增
    private static $_details = array(
        self::SALE_TYPE_WHATEVER => '随买随卖',
        self::SALE_TYPE_QUARTERLY_DIVIDENDS => '按季分红',
        self::SALE_TYPE_ONE_TIME_DEBT_SERVICE => '一次性还本付息',
    );
    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($type)
    {
        return isset(self::$_details[$type]) ? self::$_details[$type] : "";
    }
}
