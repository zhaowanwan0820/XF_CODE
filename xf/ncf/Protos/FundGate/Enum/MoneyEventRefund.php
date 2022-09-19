<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MoneyEventRefund extends AbstractEnum
{
    /** 回款状态（解冻时，是否需要回款） **/
    const UNLOCK_NOT_NEED_REFUND = 1;//不需要
    const UNLOCK_NEED_REFUND = 2;//需要

    private static $_details = array(
        self::UNLOCK_NOT_NEED_REFUND => "不需要",
        self::UNLOCK_NEED_REFUND => "需要",
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
