<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class RefundReason extends AbstractEnum
{
    /* 退款原因 */
    const TYPE_PURCHASE_FAILED = 1; // 申购失败
    const TYPE_PURCHASE_SHARE_FAILED = 2; // 份额确认失败
    const TYPE_PURCHASE_SUCCESS = 3;//申购成功

    private static $_details = array(
        self::TYPE_PURCHASE_FAILED => "申购失败",
        self::TYPE_PURCHASE_SHARE_FAILED => "份额确认失败",
        self::TYPE_PURCHASE_SUCCESS => "申购成功",
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
