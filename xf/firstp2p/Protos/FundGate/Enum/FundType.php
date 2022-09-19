<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundType extends AbstractEnum
{
    // 基金类型
    const TYPE_ORDINARY = 0;//股票型
    const TYPE_SHORT_DEBT = 1;//短债型
    const TYPE_MONEY = 2;//货币型
    const TYPE_QDII = 5;//QDII型
    const TYPE_SPECIFIC = 8;//私募型
    const TYPE_MIX = 10;//混合型（对应36进制字符A）
    const TYPE_SLANT_STOCK = 11;//偏股型（对应36进制字符B）
    const TYPE_BOND = 14;//债券型（对应36进制字符E）
    const TYPE_INDEX = 15;//指数型（对应36进制字符F）

    private static $_details = [
        self::TYPE_ORDINARY => '股票型',
        self::TYPE_SHORT_DEBT => '短债型',
        self::TYPE_MONEY => '货币型',
        self::TYPE_QDII => 'QDII型',
        self::TYPE_SPECIFIC => '私募型',
        self::TYPE_MIX => '混合型',
        self::TYPE_SLANT_STOCK => '偏股型',
        self::TYPE_BOND => '债券型',
        self::TYPE_INDEX => '指数型',
    ];

    //公募基金类型MAP（货基属于特殊的公募基金，不包含在内）
    private static $_publicFundTypes = [
        self::TYPE_ORDINARY => '股票型',
        self::TYPE_MIX => '混合型',
        self::TYPE_BOND => '债券型',
        self::TYPE_INDEX => '指数型',
        self::TYPE_QDII => 'QDII型',
    ];

    //允许撤单的基金类型MAP
    private static $_allowedWithdrawTypes = [
        self::TYPE_ORDINARY => '股票型',
        self::TYPE_MIX => '混合型',
        self::TYPE_BOND => '债券型',
        self::TYPE_MONEY => '货币型',
        self::TYPE_INDEX => '指数型',
        self::TYPE_QDII => 'QDII型',
    ];

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($type)
    {
        return isset(self::$_details[$type]) ? self::$_details[$type] : "";
    }

    public static function getPublicFundTypeMap()
    {
        return self::$_publicFundTypes;
    }

    public static function getAllowedWithdrawTypeMap()
    {
        return self::$_allowedWithdrawTypes;
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
