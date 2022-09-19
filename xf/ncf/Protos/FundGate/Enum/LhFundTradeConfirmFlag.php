<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class LhFundTradeConfirmFlag extends AbstractEnum
{
    // 联合基金份额确认状态
    const LHFUND_TRADE_CONFIRM_FAIL = 0; // 确认失败
    const LHFUND_TRADE_CONFIRM_SUCC = 1; // 确认成功
    const LHFUND_TRADE_CONFIRM_PART = 2; // 部分确认
    const LHFUND_TRADE_CONFIRM_INST_SUCC = 3; // 实时确认成功
    const LHFUND_TRADE_CONFIRM_CANCEL = 4; // 已撤销交易
    const LHFUND_TRADE_CONFIRM_BEHAVIOUR = 5; // 行为确认
    const LHFUND_TRADE_CONFIRM_INIT = 9; // 未处理

    private static $_details = array(
        self::LHFUND_TRADE_CONFIRM_FAIL => "确认失败",
        self::LHFUND_TRADE_CONFIRM_SUCC => "确认成功",
        self::LHFUND_TRADE_CONFIRM_PART => "部分确认",
        self::LHFUND_TRADE_CONFIRM_INST_SUCC => "实时确认成功",
        self::LHFUND_TRADE_CONFIRM_CANCEL => "已撤销交易",
        self::LHFUND_TRADE_CONFIRM_BEHAVIOUR => "行为确认",
        self::LHFUND_TRADE_CONFIRM_INIT => "未处理",
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
