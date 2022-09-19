<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class RiskAbility extends AbstractEnum
{
    // 基金类型
    const TYPE_SAFETY = 1; // 安全型
    const TYPE_CONSERVATIVE = 2; //保守型
    const TYPE_STEADINESS= 3; // 稳健型
    const TYPE_POSITIVE = 4; // 积极型
    const TYPE_AGGRESSIVE = 5;// 进取型

    private static $_details = array(
        self::TYPE_SAFETY => '安全型',
        self::TYPE_CONSERVATIVE => '保守型',
        self::TYPE_STEADINESS => '稳健型',
        self::TYPE_POSITIVE => '积极型',
        self::TYPE_AGGRESSIVE => '进取型',
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
