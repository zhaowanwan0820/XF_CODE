<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealRepayOpLogEnum extends AbstractEnum {
    const REPAY_TYPE_NORMAL = 1; // 正常还款
    const REPAY_TYPE_PRE = 2;   //提前还款
    const REPAY_TYPE_PRE_SELF = 3;  // 借款人前台自助发起
    const REPAY_TYPE_DAIFA = 4 ;//代发还款
}
