<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealGrantFeeEnum extends AbstractEnum {

    const OVER_TIME_SECONDS = 3600; // 超时关单时间

    const ALARM_DEAL_GRANT_FEE = 'deal_grant_fee'; // 扣费超时关单

}