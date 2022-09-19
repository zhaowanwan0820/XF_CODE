<?php
namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;

class RepayPoolEnum extends AbstractEnum {

    const STATUS_INIT = 0; // 忽略状态
    const STATUS_UNUSE = 1; //未处理
    const STATUS_DONE = 2; //已处理
    const STATUS_IGNORE = 3; // 忽略状态终态
}
