<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ActivityType extends AbstractEnum
{
    // 基金类型
    const TYPE_ORDINARY_ACTIVITY = 0; // 普通活动
    const TYPE_TOPIC_ACTIVITY = 1; // 专题活动
    private static $_details = [
        self::TYPE_ORDINARY_ACTIVITY => '普通活动',
        self::TYPE_TOPIC_ACTIVITY => '专题活动',
   ];

    public static function getMap()
    {
        return self::$_details;
    }

}
