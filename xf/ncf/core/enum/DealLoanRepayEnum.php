<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealLoanRepayEnum extends AbstractEnum {

    const MONEY_PRINCIPAL = 1; // 本金
    const MONEY_INTREST = 2; // 利息
    const MONEY_PREPAY = 3; // 提前还款
    const MONEY_COMPENSATION = 4; // 提前还款补偿金
    const MONEY_IMPOSE = 5; // 逾期罚息
    const MONEY_MANAGE = 6; // 管理费
    const MONEY_PREPAY_INTREST = 7; // 提前还款利息
    const MONEY_COMPOUND_PRINCIPAL = 8; // 利滚利赎回本金
    const MONEY_COMPOUND_INTEREST = 9; // 利滚利赎回利息

    const STATUS_NOTPAYED = 0; // 未还
    const STATUS_ISPAYED = 1; // 已还
    const STATUS_CANCEL = 2; // 因提前还款而取消

}
