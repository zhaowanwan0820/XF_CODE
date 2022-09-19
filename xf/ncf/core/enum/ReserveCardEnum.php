<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use core\enum\DealEnum;

class ReserveCardEnum extends AbstractEnum {

    /**
     * 状态-0:无效
     * @var int
     */
    const STATUS_UNVALID = 0;

    /**
     * 状态-1:有效
     * @var int
     */
    const STATUS_VALID = 1;


}
