<?php

namespace core\enum\duotou;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class P2pDealEnum extends AbstractEnum {
    const STATUS_NOT_MAPPING        = 0; // 未匹配
    const STATUS_SUCCESS_MAPPING    = 1; // 已匹配
    const STATUS_FAIL_DEAL          = 2; // 已流标
    const STATUS_DEAL_REPAY         = 3; // 已还款完成

    const STATUS_HAS_LOANS_NO       = 0; // 标的未放款
    const STATUS_HAS_LOANS_YES      = 1; // 标的已放款
}
