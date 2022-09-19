<?php

namespace core\enum\duotou;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class LoanMappingContractEnum extends AbstractEnum {

    const STATUS_MAPPING_SUCCESS    = 0; // 匹配成功
    const STATUS_BID_P2P_SUCCESS    = 1; // 已投向p2p
    const STATUS_HAS_REPAY          = 2; // 已还款

}
