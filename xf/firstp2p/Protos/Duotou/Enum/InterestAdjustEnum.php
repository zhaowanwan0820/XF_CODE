<?php

namespace NCFGroup\Protos\Duotou\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

/**
 * 利息调整
 * Class InterestAjustEnum
 * @package NCFGroup\Protos\Duotou\Enum
 */
class InterestAdjustEnum extends AbstractEnum {

    const STATUS_NORMAL = 0; // 正常状态
    const STATUS_FINISH = 1; // 调息后已完成结息
}
