<?php

namespace NCFGroup\Protos\Duotou\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealLoanEnum extends AbstractEnum {

    // mapping_status 状态
    const MAPPING_STATUS_NORMAL                 = 0; // 正常状态
    const MAPPING_STATUS_REPAY_OFFSET_SUCCESS   = 1; // 还款抵消成功
    const MAPPING_STATUS_MAPPING_SUCCESS        = 2; // 匹配成功
    const MAPPING_STATUS_REDEMPTION             = 3; // 已经赎回

    // status 状态
    const DEAL_LOAN_BID_SUCCESS         = 1; // 投资成功
    const DEAL_LOAN_MAPPING_SUCCESS     = 2; // 匹配成功
    const DEAL_LOAN_REDEMPTION_APPLY    = 3; // 赎回申请中
    const DEAL_LOAN_REDEMPTION_SUCCESS  = 4; // 赎回成功
    const DEAL_LOAN_FINISH              = 5; // 已结清
    const DEAL_LOAN_REVOKE              = 6; // 已撤销(已取消)

    const DEAL_LOAN_REPAY_NORMAL            = 0; // 还款成功状态
    const DEAL_LOAN_REPAY_MAPPING_SUCCESS   = 1; // 还款数据匹配成功
}
