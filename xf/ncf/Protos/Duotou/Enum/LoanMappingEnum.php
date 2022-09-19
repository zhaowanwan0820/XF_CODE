<?php

namespace NCFGroup\Protos\Duotou\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class LoanMappingEnum extends AbstractEnum {
    const STATUS_NORMAL         = 1; // 正常
    const STATUS_HAS_REDEEMED   = 2; // 已赎回
    const STATUS_HAS_REPAY      = 3; // 已还款

    const STATUS_REPAY_NORMAL       = 0; // 0 初始化
    const STATUS_REPAY_COMPUTED     = 1; // 1 已计算还款金额
//    const STATUS_REPAY_FIXED        = 2; // 已修正
//    const STATUS_REPAY_TRANSFERED   = 3; // 已迁移

}
