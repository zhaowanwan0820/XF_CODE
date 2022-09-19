<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealAgencyEnum extends AbstractEnum {

    const TYPE_GUARANTEE    =  1; // 担保
    const TYPE_CONSULT      =  2; // 咨询
    const TYPE_PLATFORM     =  3; // 平台
    const TYPE_PAYMENT      =  4; // 支付
    const TYPE_MANAGEMENT   =  5; // 管理
    const TYPE_ADVANCE      =  6; // 垫付
    const TYPE_ENTRUST      =  7; // 受托
    const TYPE_RECHARGE     =  8; // 代充值
    const TYPE_JYS          =  9; // 交易所
    const TYPE_CANAL        = 10; // 渠道机构
}