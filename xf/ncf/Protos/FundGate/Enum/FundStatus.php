<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundStatus extends AbstractEnum
{
    // 正常
    const STATUS_ONLINE    = 0; // 可以申购、可以赎回

    // 可以投资，不可赎回
    const STATUS_SUBSCRIBE = 1; // 可以认购、不可赎回 -- 募集期
    const STATUS_APPLY     = 2; // 可以申购、不可赎回 -- 停止赎回期

    // 不可投资，可以赎回 [10, +oo) 不可投资
    const STATUS_REDEEM    = 10; // 不可申购、可以赎回       -- 暂停投资

    // 不可投资，不可赎回
    const STATUS_OFFLINE   = 11; // 不可认购/申购、不可赎回  -- 暂停投资 -- 封闭期
    const STATUS_CLOSE     = 12; // 不可认购/申购、不可赎回  -- 投资结束

    // 预约
    const STATUS_BOOKING_BEGIN = 101; // 预约开始
    const STATUS_BOOKING_END   = 102; // 预约结束

    private static $_details = array(
        self::STATUS_ONLINE => "可以申购和赎回",
        self::STATUS_SUBSCRIBE => "可以认购，不可赎回（募集期）",
        self::STATUS_APPLY => "可以申购，不可赎回（停止赎回期）",
        self::STATUS_REDEEM => "不可申购，可以赎回（暂停投资）",
        self::STATUS_OFFLINE => "不可认购、申购和赎回（暂停投资，封闭期）",
        self::STATUS_CLOSE => "不可认购、申购和赎回（投资结束）",
        self::STATUS_BOOKING_BEGIN => "预约开始",
        self::STATUS_BOOKING_END => "预约结束",
    );

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($status)
    {
        return isset(self::$_details[$status]) ? self::$_details[$status] : "";
    }

    //申购/认购界面，提交按钮状态配置
    public static function getBuyState($status)
    {
        switch($status){
            case self::STATUS_ONLINE:
            case self::STATUS_APPLY:
                $buyState = ["title" => "申购", "flag" => 1];//可以申购
                break;
            case self::STATUS_REDEEM:
                $buyState = ["title" => "暂停申购", "flag" => 0];//暂停申购
                break;
            case self::STATUS_SUBSCRIBE:
                $buyState = ["title" => "认购", "flag" => 1];//可以认购
                break;
            default:
                $buyState = ["title" => "停止申购", "flag" => 0];//停止交易
        }
        return $buyState;
    }

    //赎回界面，提交按钮状态配置
    public static function getRedeemState($status)
    {
        switch($status){
            case self::STATUS_ONLINE:
            case self::STATUS_REDEEM:
                $redeemState = ["title" => "赎回", "flag" => 1];//可以赎回
                break;
            case self::STATUS_APPLY:
                $redeemState = ["title" => "暂停赎回", "flag" => 0];//暂停赎回
                break;
            default:
                $redeemState = ["title" => "停止赎回", "flag" => 0];//停止交易
        }
        return $redeemState;
    }

    //持仓界面，提交按钮状态配置
    public static function getActionItems($status)
    {
        switch($status){
            case self::STATUS_ONLINE://可以申购，可以赎回
                $actionItems = [
                    "buy" => ["title" => "申购", "flag" => 1],
                    "redeem" => ["title" => "赎回", "flag" => 1],
                ];
                break;
            case self::STATUS_APPLY://可以申购，暂停赎回
                $actionItems = [
                    "buy" => ["title" => "申购", "flag" => 1],
                    "redeem" => ["title" => "暂停赎回", "flag" => 0],
                ];
                break;
            case self::STATUS_REDEEM://暂停申购，可以赎回
                $actionItems = [
                    "buy" => ["title" => "暂停申购", "flag" => 0],
                    "redeem" => ["title" => "赎回", "flag" => 1],
                ];
                break;
            case self::STATUS_SUBSCRIBE://可以认购，不可赎回
                $actionItems = [
                    "buy" => ["title" => "认购", "flag" => 1],
                    "redeem" => ["title" => "暂停赎回", "flag" => 0],
                ];
                break;
            default://停止申购，停止赎回
                $actionItems = [
                    "buy" => ["title" => "停止申购", "flag" => 0],
                    "redeem" => ["title" => "停止赎回", "flag" => 0],
                ];
        }
        return $actionItems;
    }
}
