<?php

namespace NCFGroup\Protos\Duotou\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class P2pDealRepayEnum extends AbstractEnum {
    const STATUS_REPAY = 0; // 还款
    const STATUS_REPAY_FIXED = 1; // 还款修正完成
//    const STATUS_REPAY_TRANSFER = 2; // 还款转账完成
    const STATUS_FINISHED = 2; // 已处理完成
}
