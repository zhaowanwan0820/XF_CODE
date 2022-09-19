<?php

namespace NCFGroup\Protos\Duotou\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class RedemptionApplyEnum extends AbstractEnum {

    // status 状态
    const STATUS_WAITING                = 0; // 赎回申请中
    const STATUS_REPAY_OFFSET_SUCCESS   = 1; // 赎回还款匹配成功
//    const STATUS_MAPPING_DOING          = 2; // 赎回匹配中
    const STATUS_MAPPING_SUCCESS        = 2; // 赎回匹配成功
    const STATUS_TRANSFER_ING           = 3; // 赎回转账中
    const STATUS_SUCCESS                = 4; // 赎回成功 已到账

}
