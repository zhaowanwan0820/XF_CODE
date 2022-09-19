<?php
namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ThirdpartyDkEnum extends AbstractEnum {
    const REQUEST_STATUS_WATTING = 0;
    const REQUEST_STATUS_PROCESSING = 1;
    const REQUEST_STATUS_SUCCESS = 2;
    const REQUEST_STATUS_FAIL = 3;

    //服务类型
    const SERVICE_TYPE_DK       = 1; //主动代扣
    const SERVICE_TYPE_TRANSFER = 2; //划转
    const SERVICE_TYPE_PREPAY   = 3; //提前还款
    const SERVICE_TYPE_REPAY    = 4; //正常还款
    const SERVICE_TYPE_BANKSIGN = 5; //银行卡签署
}