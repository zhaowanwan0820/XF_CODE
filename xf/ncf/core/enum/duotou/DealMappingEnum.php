<?php

namespace core\enum\duotou;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealMappingEnum extends AbstractEnum {
    const STATUS_MAPPING_SUCCESS = 0; // 已匹配
    const STATUS_HAS_REPAY = 1; // 已还款
}
