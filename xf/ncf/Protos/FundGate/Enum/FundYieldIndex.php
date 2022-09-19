<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundYieldIndex extends AbstractEnum
{
    const DAILY_PROFIT = 'daily_profit';//万份收益
    const WEEKLY_YIELD = 'weekly_yield';//七日年化收益
    const RECENT_ONE_MONTH_YIELD = 'one_month_yield';//近一个月收益
    const RECENT_THREE_MONTH_YIELD = 'three_month_yield';//近三个月收益
    const RECENT_HALF_YEAR_YIELD = 'half_year_yield';//近半年收益
    const RECENT_ONE_YEAR_YIELD = 'one_year_yield';//近一年收益

    private static $_moneyFund = array(
        self::WEEKLY_YIELD => '七日年化收益',
        self::DAILY_PROFIT => '万份收益',
    );

    private static $_publicFund = array(
        self::RECENT_ONE_MONTH_YIELD => '近一个月收益',
        self::RECENT_THREE_MONTH_YIELD => '近三个月收益',
        self::RECENT_HALF_YEAR_YIELD => '近半年收益',
        self::RECENT_ONE_YEAR_YIELD => '近一年收益',
    );

    private static $_publicFundShort = array(
        self::RECENT_ONE_MONTH_YIELD => '一个月',
        self::RECENT_THREE_MONTH_YIELD => '三个月',
        self::RECENT_HALF_YEAR_YIELD => '半年',
        self::RECENT_ONE_YEAR_YIELD => '一年',
    );

    //收益指标与fund表字段映射关系MAP
    private static $_yieldIndex2Field = [
        self::DAILY_PROFIT => "dailyProfit",
        self::WEEKLY_YIELD => "weeklyYield",
        self::RECENT_ONE_MONTH_YIELD => "oneMonthYield",
        self::RECENT_THREE_MONTH_YIELD => "threeMonthYield",
        self::RECENT_HALF_YEAR_YIELD => "halfYearYield",
        self::RECENT_ONE_YEAR_YIELD => "oneYearYield",
    ];

    public static function getMoneyFundIndex()
    {
        return self::$_moneyFund;
    }

    public static function getMoneyFundIndexName($index)
    {
        return isset(self::$_moneyFund[$index]) ? self::$_moneyFund[$index] : "";
    }

    public static function getPublicFundIndex()
    {
        return self::$_publicFund;
    }

    public static function getPublicFundIndexName($index)
    {
        return isset(self::$_publicFund[$index]) ? self::$_publicFund[$index] : "";
    }

    public static function getPublicFundIndexShort()
    {
        return self::$_publicFundShort;
    }

    public static function getYieldField($index)
    {
        return isset(self::$_yieldIndex2Field[$index]) ? self::$_yieldIndex2Field[$index] : "";
    }
}
