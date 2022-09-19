<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class StockExchange extends AbstractEnum
{
    const SHENZHEN = 1;
    const SHANGHAI = 2;
    const PLATECONCEPT = 3;//板块概念
    const PLATEINDUSTRY = 4;//板块行业
    const PLATEREGION = 5;//板块地域

    protected static $details = array(
        self::SHENZHEN => '深圳交易所',
        self::SHANGHAI => '上海交易所',
        self::PLATECONCEPT => '板块概念',
        self::PLATEINDUSTRY => '板块行业',
        self::PLATEREGION => '板块地域',
        );
    protected static $relation = array(
        'SZ' => self::SHENZHEN,
        'SH' => self::SHANGHAI,
        'BKGN' => self::PLATECONCEPT,
        'BKHY' => self::PLATEINDUSTRY,
        'BKDY' => self::PLATEREGION,
        );
    public static function getExchangeMap()
    {
        return self::$details;
    }

    public static function getRelation()
    {
        return self::$relation;
    }
}
