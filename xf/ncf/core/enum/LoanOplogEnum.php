<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class LoanOplogEnum extends AbstractEnum
{
    const OP_TYPE_MAKE_LOAN = 0; // 放款操作
    const OP_TYPE_AUTO_MAKE_LOAN = 3; // 自动放款
}
