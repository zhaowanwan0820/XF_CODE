<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealLoanRepayCalendarEnum extends AbstractEnum
{
    const CALENDAR_INIT_FINISH = 'CALENDAR_HASH_NEW';
    const NOREPAY_INTEREST   = 'norepay_interest';
    const REPAY_INTEREST     = 'repay_interest';
    const NOREPAY_PRINCIPAL  = 'norepay_principal';
    const REPAY_PRINCIPAL    = 'repay_principal';
    const PREPAY_PRINCIPAL   = 'prepay_principal';
    const PREPAY_INTEREST    = 'prepay_interest';

    const BEGIN_YEAR = 2014; // 日历开始的年份

    public static $moneyTypes = array(
        self::NOREPAY_INTEREST,
        self::REPAY_INTEREST,
        self::NOREPAY_PRINCIPAL,
        self::REPAY_PRINCIPAL,
        self::PREPAY_PRINCIPAL,
        self::PREPAY_INTEREST
    );

}
