<?php
namespace NCFGroup\Protos\Bonus\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MoneyOrderEnum extends AbstractEnum {

    //黄金相关 1-100
    const RECHARGE = 1; // 红包充值
    const MALL_GOLD_COIN = 2; // 购买商城金币

    // 所有业务子类型, 此map必须定义，否则黄金订单对账服务验证失败
    public static $subtypeDesc = [
        self::RECHARGE => '红包充值',
        self::MALL_GOLD_COIN => '购买商城金币',
    ];
}

