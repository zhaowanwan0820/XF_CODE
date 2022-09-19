<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class OrderStatus extends AbstractEnum
{
    /* 订单状态 */
    const STATUS_INIT     = 0; // 暂存
    const STATUS_ACCEPT   = 1; // 受理
    const STATUS_SUCCESS  = 2; // 成功
    const STATUS_FAILD    = 3; // 失败
    const STATUS_ABANDON  = 4; // 已撤单
    const STATUS_PAY_SUCC = 5; // 支付成功
    const STATUS_IN_PROGRESS = 6;//处理中
    const STATUS_ABANDON_IN_PROGRESS = 7;//撤单处理中

    private static $_details = array(
        self::STATUS_INIT => "暂存",
        self::STATUS_ACCEPT => "受理",
        self::STATUS_SUCCESS => "成功",
        self::STATUS_FAILD => "失败",
        self::STATUS_ABANDON_IN_PROGRESS => "撤单处理中",
        self::STATUS_ABANDON => "已撤单",
        self::STATUS_PAY_SUCC => "支付成功",
        self::STATUS_IN_PROGRESS => "处理中",
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
