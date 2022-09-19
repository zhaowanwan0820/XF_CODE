<?php

namespace NCFGroup\Protos\Duotou\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealLoanCancelEnum extends AbstractEnum {

    // 取消状态
    const CANCEL_STATUS_NORMAL  = 0; // 正常
    const CANCEL_STATUS_CHANGE  = 1; // 投资记录已变更
    const CANCEL_STATUS_RESTORE = 2; // 投资记录已复原
    const CANCEL_STATUS_REDEEM  = 3; // 投资记录已赎回
    const CANCEL_STATUS_REVOKE  = 4; // 投资记录已撤销

    //取消类型
    const CANCEL_TYPE_NORMAL  = 0; //取消类型正常
    const CANCEL_TYPE_REDEEM  = 1; //取消类型赎回
    const CANCEL_TYPE_REVOKE  = 2; //取消类型撤销

}
