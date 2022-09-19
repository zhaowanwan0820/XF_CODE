<?php
/**
 * 枚举一些合同服务常用的标识
 */
namespace NCFGroup\Protos\Contract\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ContractServiceEnum extends AbstractEnum
{
    // 服务类型
    const SERVICE_TYPE_DEAL = 1; // 标的
    const SERVICE_TYPE_PROJECT = 2; // 项目
    const SERVICE_TYPE_GOLD_DEAL = 3; // 黄金标的
    const SERVICE_TYPE_DARK_MOON_DEAL = 4; // 暗月项目-darkmoon

    const TYPE_P2P = 0; //p2p项目
    const TYPE_DT = 1; //智多鑫项目
    const TYPE_GOLD = 2; //黄金项目
    const TYPE_RESERVATION = 4; //随心约普惠项目
    const TYPE_RESERVATION_SUPER = 5; //随心约尊享项目

    const SOURCE_TYPE_DEAL = 0; //网贷
    const SOURCE_TYPE_COMPOUND = 1; //通知贷
    const SOURCE_TYPE_EXCHANGE = 2; //交易所
    const SOURCE_TYPE_EXCLUSIVE = 3; //专享
    const SOURCE_TYPE_PETTYLOAN = 5; //小贷
    const SOURCE_TYPE_PH = 0; //普惠标的
    const SOURCE_TYPE_GOLD = 100; //黄金
    const SOURCE_TYPE_OFFLINE_EXCHANGE = 200; //线下交易所
    const SOURCE_TYPE_DT_CONTRACT = 101; //智多鑫
    const SOURCE_TYPE_RESERVATION = 102; //随心约-普惠
    const SOURCE_TYPE_RESERVATION_SUPER = 103; //随心约-尊享

    const RESERVATION_PROJECT_ID = 8888; //随心约没有项目id，所以定义一个项目id
}
