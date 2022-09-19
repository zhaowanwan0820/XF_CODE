<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class LhFundPurchaseCode extends AbstractEnum
{
    // 联合基金交易码
    const LHFUND_PURCHASE_CODE_APPLY = '022'; // 申购
    const LHFUND_PURCHASE_CODE_SUBSCRIBE = '020'; // 认购
    const LHFUND_PURCHASE_CODE_REDEEM = '024'; // 赎回

    private static $_details = array(
        self::LHFUND_PURCHASE_CODE_APPLY => "申购",
        self::LHFUND_PURCHASE_CODE_SUBSCRIBE => "认购",
        self::LHFUND_PURCHASE_CODE_REDEEM => "赎回",
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
