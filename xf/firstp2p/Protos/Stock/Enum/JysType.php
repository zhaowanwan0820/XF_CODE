<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class JysType extends AbstractEnum
{
    const SHANGHAI = 'SH';
    const SHENZHEN = 'SZ';
    protected static $details = [
        self::SHANGHAI => "沪市",
        self::SHENZHEN => "深市",
    ];

    public static function getName($jys)
    {
        return isset(self::$details[$jys]) ? self::$details[$jys] : "未知";
    }
}
