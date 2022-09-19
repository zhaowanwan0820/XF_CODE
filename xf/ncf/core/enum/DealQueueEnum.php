<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealQueueEnum extends AbstractEnum {
    //投资期限单位
    const INVEST_DEADLINE_UNIT_DAY = 1; //天
    const INVEST_DEADLINE_UNIT_MONTH = 2; //月
    const INVEST_DEADLINE_UNIT_NULL = 0; //无，用于区别增加本字段之前和之后的队列
}
