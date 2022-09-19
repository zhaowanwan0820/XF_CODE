<?php

namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class YtFundType extends AbstractEnum
{
    const FUND_CHANNEL_NUM = 8;//基金频道首页展示基金数

    const TYPE_ALL = -1;
    // 基金类型(8\H\I\K\T私募)
    const TYPE_ORDINARY = 0;//股票型
    const TYPE_SHORT_DEBT = 1;//短债型
    const TYPE_MONEY = 2;//货币型
    const TYPE_ETF = 3;//ETF
    const TYPE_GUANRANTEED = 4; //保本基金
    const TYPE_QDII = 5;//QDII型
    const TYPE_SPECIFIC = 8;//专户型
    const TYPE_SHORT_TERM = 9;//超短期
    const TYPE_MIX = 10;//混合型（对应36进制字符A）
    const TYPE_SLANT_STOCK = 11;//偏股型（对应36进制字符B）
    const TYPE_STOCK_BOND = 12;//股债平衡型（对应36进制字符C）
    const TYPE_SLANT_BOND = 13;//偏债型（对应36进制字符D）
    const TYPE_BOND = 14;//债券型（对应36进制字符E）
    const TYPE_INDEX = 15;//指数型（对应36进制字符F）
    const TYPE_LOF = 16;//LOF（对应36进制字符G）
    const TYPE_PRIVATE = 17;//私募型（对应36进制字符H）
    const TYPE_TRUST = 18;//信托型（对应36进制字符I）
    const TYPE_COLLECTIVE = 19;//集合理财（对应36进制字符J）
    const TYPE_SPECIAL = 20;//专项资产管理（对应36进制字符K）
    const TYPE_CLASS = 21;//分级债基金（对应36进制字符L）
    const TYPE_SMALL_COLLECTIVE = 29;//小集合理财产品（对应36进制字符M）


    const WEEKLY_YIELD = 'weekly_yield';//七日年化收益
    const RECENT_THREE_MONTH_YIELD = 'three_month_yield';//近三个月收益
    const DAILY_PROFIT = "daily_profit";//万份收益
    const NET_VALUE = "net_value";//最新净值


    private static $_details = [
        self::TYPE_ALL => '全部类型',
        self::TYPE_ORDINARY => '股票型',
        self::TYPE_SHORT_DEBT => '短债型',
        self::TYPE_MONEY => '货币型',
        self::TYPE_ETF => 'ETF',
        self::TYPE_GUANRANTEED => '保本型',
        self::TYPE_QDII => 'QDII基金',
        self::TYPE_SPECIFIC => '专户产品',
        self::TYPE_SHORT_TERM => '超短期理财产品',
        self::TYPE_MIX => '混合型',
        self::TYPE_SLANT_STOCK => '偏股型',
        self::TYPE_STOCK_BOND => '股债平衡型',
        self::TYPE_SLANT_BOND => '偏债型',
        self::TYPE_BOND => '债券型',
        self::TYPE_INDEX => '指数型',
        self::TYPE_LOF => 'LOF',
        self::TYPE_PRIVATE => '私募',
        self::TYPE_TRUST => '信托',
        self::TYPE_COLLECTIVE => '集合理财',
        self::TYPE_SPECIAL => '专项资产管理',
        self::TYPE_CLASS => '分级债型',
        self::TYPE_SMALL_COLLECTIVE => '小集合理财产品',
    ];


    private static $_fundYieldType = [
        self::TYPE_ORDINARY => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_SHORT_DEBT => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_MONEY => self::WEEKLY_YIELD,

        self::TYPE_ETF => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_GUANRANTEED => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_QDII => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_SPECIFIC => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_SHORT_TERM => self::RECENT_THREE_MONTH_YIELD,

        self::TYPE_MIX => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_SLANT_STOCK => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_STOCK_BOND => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_SLANT_BOND => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_BOND => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_INDEX => self::RECENT_THREE_MONTH_YIELD,

        self::TYPE_LOF => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_PRIVATE => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_TRUST => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_COLLECTIVE => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_SPECIAL => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_CLASS => self::RECENT_THREE_MONTH_YIELD,
        self::TYPE_SMALL_COLLECTIVE => self::RECENT_THREE_MONTH_YIELD
    ];

    //货币基金与非货币基金进行区分
    private static $_fundType = [
        self::TYPE_ORDINARY => '1',
        self::TYPE_SHORT_DEBT => '1',
        self::TYPE_MONEY => '0',

        self::TYPE_ETF => '1',
        self::TYPE_GUANRANTEED => '1',
        self::TYPE_QDII => '1',
        self::TYPE_SPECIFIC => '1',
        self::TYPE_SHORT_TERM => '1',

        self::TYPE_MIX => '1',
        self::TYPE_SLANT_STOCK => '1',
        self::TYPE_STOCK_BOND => '1',
        self::TYPE_SLANT_BOND => '1',
        self::TYPE_BOND => '1',
        self::TYPE_INDEX => '1',

        self::TYPE_LOF => '1',
        self::TYPE_PRIVATE => '1',
        self::TYPE_TRUST => '1',
        self::TYPE_COLLECTIVE => '1',
        self::TYPE_SPECIAL => '1',
        self::TYPE_CLASS => '1',
        self::TYPE_SMALL_COLLECTIVE => '1'
    ];

    //组合排序字段
    private static $_fundOrder = [
       '0' => array(
           '0' => array(
               '0' => 'money_netValue_asc',
               '1' => 'money_netValue_desc'
           ),
           '1' => array(
               '0' => 'money_yield_asc',
               '1' => 'money_yield_desc'
           )
       ),
        '1' => array(
            '0' => array(
                '0' => 'notMoney_netValue_asc',
                '1' => 'notMoney_netValue_desc'
            ),
            '1' => array(
                '0' => 'notMoney_yield_asc',
                '1' => 'notMoney_yield_desc'
            )
        )
    ];

    //收益指标与fund表字段映射关系MAP
    private static $_yieldIndex2Field = [
        self::WEEKLY_YIELD => "weeklyYield",
        self::RECENT_THREE_MONTH_YIELD => "threeMonthYield",
    ];

  //收益指标与fund表字段映射关系MAP
    private static $_yieldIndex3Field = [
        self::WEEKLY_YIELD => "dailyProfit",
        self::RECENT_THREE_MONTH_YIELD => "netValue",
    ];


    private static $_fund = array(
        'weeklyYield' => '七日年化收益',
        'threeMonthYield' => '近三个月收益',
        'dailyProfit' => '万份收益',
        'netValue' => '最新净值',

    );


    public static function getFundIndexName($index)
    {
        return isset(self::$_fund[$index]) ? self::$_fund[$index] : "";
    }


    public static function getFundYieldType()
    {
        return self::$_fundYieldType;
    }

    public static function getFundYType()
    {
        return self::$_fundType;
    }

    public static function getFundOrder()
    {
        return self::$_fundOrder;
    }

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($type)
    {
        return isset(self::$_details[$type]) ? self::$_details[$type] : "";
    }


    public static function getYieldField($index)
    {
        return isset(self::$_yieldIndex2Field[$index]) ? self::$_yieldIndex2Field[$index] : "";
    }

    public static function getNetValueField($index)
    {
        return isset(self::$_yieldIndex3Field[$index]) ? self::$_yieldIndex3Field[$index] : "";
    }

    /**
     * 因fund表里的type字段类型为tinyint，而宜投系统里有数字、字母两种类型，故执行36进制转10进制操作
     * @param $type
     * @return int
     */
    public static function convertTypeFrom36To10($type)
    {
        return (int)base_convert($type, 36, 10);
    }
}
