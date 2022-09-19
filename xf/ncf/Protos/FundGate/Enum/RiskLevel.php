<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class RiskLevel extends AbstractEnum
{
    const RISKLEVEL_LOW = 0;//1         // 低风险
    const RISKLEVEL_MIDDLE_LOW = 1;//2  // 中低风险
    const RISKLEVEL_MIDDLE = 2;//3      // 中风险
    const RISKLEVEL_MIDDLE_HIGH = 3;//4 // 中高风险
    const RISKLEVEL_HIGH = 4;//4        // 高风险
    private static $_details = array(
                       self::RISKLEVEL_LOW => '低风险',
                       self::RISKLEVEL_MIDDLE_LOW => '中低风险',
                       self::RISKLEVEL_MIDDLE => '中风险',
                       self::RISKLEVEL_MIDDLE_HIGH => '中高风险',
                       self::RISKLEVEL_HIGH => '高风险',
    );

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($level)
    {
        return isset(self::$_details[$level]) ? self::$_details[$level] : "";
    }
}
