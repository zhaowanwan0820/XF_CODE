<?php
namespace NCFGroup\Protos\Ptp\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealEnum extends AbstractEnum {

    // 标类型
    const DEAL_TYPE_GENERAL = 0; //普通标
    const DEAL_TYPE_COMPOUND = 1;  //通知贷
    const DEAL_TYPE_EXCHANGE = 2;  //交易所
    const DEAL_TYPE_EXCLUSIVE = 3;  //专享
    const DEAL_TYPE_ALL_P2P = "0,1";  //所有p2p包含通知贷和普通标

    // 此类型为虚拟类型，deal表中不存在类型为4的记录
    const DEAL_TYPE_SUPERVISION = 4; // 走存管标的类型不含通知贷
    const DEAL_TYPE_PETTYLOAN = 5;//小贷
    // 黄金项目标中的类型，p2p deal中不做记录
    const DEAL_TYPE_GOLD = 100;
    const DEAL_TYPE_MALL = 101; // 商城消费
}
