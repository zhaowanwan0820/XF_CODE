<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class LhFundPayStatus extends AbstractEnum
{
    // 联合基金付款状态
    const LHFUND_PAY_STATUS_INIT = 0; // 未校验
    const LHFUND_PAY_STATUS_FAIL = 1; // 无效
    const LHFUND_PAY_STATUS_DONE = 2; // 有效
    const LHFUND_PAY_STATUS_DOING = 3; // 已发送扣款指令

    private static $_details = array(
        self::LHFUND_PAY_STATUS_INIT => "未校验",
        self::LHFUND_PAY_STATUS_FAIL => "无效",
        self::LHFUND_PAY_STATUS_DONE => "有效",
        self::LHFUND_PAY_STATUS_DOING => "已发送扣款指令",
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
