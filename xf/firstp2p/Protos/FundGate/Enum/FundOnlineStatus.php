<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundOnlineStatus extends AbstractEnum
{
    const NOT_DELETE = 0; // 已上线
    const IS_DELETE  = 1; // 未上线

    private static $_details = array(
        self::NOT_DELETE => "已上线",
        self::IS_DELETE => "未上线",
    );

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($status)
    {
        return isset(self::$_details[$status]) ? self::$_details[$status] : "";
    }
}
