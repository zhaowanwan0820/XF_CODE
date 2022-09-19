<?php
namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;

class MoneyOrderEnum extends AbstractEnum {

    const TYPE_SERVICE_FEE = 1; // 服务费收取
    const TYPE_REPAY_SUCCESS = 2; // 还款成功解冻
    const TYPE_APPLY_CHANGE = 3; // 还款剩余金额找零(解冻)

    public static $subtypeDesc = [
        self::TYPE_SERVICE_FEE => '服务费收取',
        self::TYPE_REPAY_SUCCESS => '还款成功解冻',
        self::TYPE_APPLY_CHANGE => '还款剩余金额退回'
    ];
}
