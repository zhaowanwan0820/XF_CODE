<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class P2pIdempotentEnum extends AbstractEnum
{
    // 回调状态
    const STATUS_WAIT = 0;      // 待处理
    const STATUS_SEND = 1;      // 已通知
    const STATUS_CALLBACK = 2;  // 已回调
    const STATUS_INVALID = 3;   // 无效订单

    // 处理状态
    const RESULT_WAIT = 0; // 待处理
    const RESULT_SUCC = 1; // 处理成功
    const RESULT_FAIL = 2; // 处理失败

    const TYPE_DEAL = 1; // 投资
    const TYPE_DEAL_LOAN = 2; // 放款
    const TYPE_DEAL_REPAY = 3; // 还款
    const TYPE_DEAL_CANCEL = 4; //流标
}
