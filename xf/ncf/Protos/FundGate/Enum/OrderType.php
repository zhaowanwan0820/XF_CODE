<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class OrderType extends AbstractEnum
{
    /* 订单类型 */
    const TYPE_PURCHASE = 0; // 申购
    const TYPE_REDEEM = 1; // 赎回
    const TYPE_SPECIFIC_BONUS = 2;//私募分红
    const TYPE_SPECIFIC_REDEEM = 3; //私募清盘
    const TYPE_SUBSCRIBE = 4;//认购
    const TYPE_BONUS_CASH = 5;//现金红利（公募）
    const TYPE_BONUS_REINVESTMENT = 6;//红利再投（公募）
    const TYPE_WITHDRAW_PURCHASE = 7;//撤销申购
    const TYPE_WITHDRAW_SUBSCRIBE = 8;//撤销认购
    const TYPE_WITHDRAW_REDEEM = 9;//撤销赎回
    const TYPE_LIQUIDATION = 10;//清盘

    private static $_details = [
        self::TYPE_PURCHASE => "申购",
        self::TYPE_SUBSCRIBE => "认购",
        self::TYPE_REDEEM => "赎回",
        self::TYPE_SPECIFIC_BONUS => "私募分红",
        self::TYPE_SPECIFIC_REDEEM => "私募清盘",
        self::TYPE_BONUS_CASH => "现金红利",
        self::TYPE_BONUS_REINVESTMENT => "红利再投",
        self::TYPE_WITHDRAW_PURCHASE => "撤销申购",
        self::TYPE_WITHDRAW_SUBSCRIBE => "撤销认购",
        self::TYPE_WITHDRAW_REDEEM => "撤销赎回",
    ];

    /* 撤单类型 */
    private static $_withdrawTypeMap = [
        self::TYPE_PURCHASE => self::TYPE_WITHDRAW_PURCHASE,
        self::TYPE_SUBSCRIBE => self::TYPE_WITHDRAW_SUBSCRIBE,
        self::TYPE_REDEEM => self::TYPE_WITHDRAW_REDEEM,
    ];

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($type)
    {
        return isset(self::$_details[$type]) ? self::$_details[$type] : "";
    }

    public static function getWithdrawType($type)
    {
        return isset(self::$_withdrawTypeMap[$type]) ? self::$_withdrawTypeMap[$type] : "";
    }

    public static function getWithdrawTypeMap()
    {
        return self::$_withdrawTypeMap;
    }
}
