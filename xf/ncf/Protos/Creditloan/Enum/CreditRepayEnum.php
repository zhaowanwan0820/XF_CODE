<?php
namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;

class CreditRepayEnum extends AbstractEnum {

    // 还款状态
    const REPAY_STATUS_INIT = 1; // 未处理
    const REPAY_STATUS_FAILED = 2; // 还款失败
    const REPAY_STATUS_PROCESSING = 3; // 还款处理中
    const REPAY_STATUS_SUCCESS = 4; // 还款成功
    const REPAY_STATUS_SERVICE_FEE_CHARGED = 5; // 服务费收取成功

    // 还款状态描述映射
    public static $repayStatusDesMap = [
        self::REPAY_STATUS_INIT => '未处理',
        self::REPAY_STATUS_FAILED => '还款失败',
        self::REPAY_STATUS_PROCESSING => '还款处理中',
        self::REPAY_STATUS_SUCCESS => '还款成功',
        self::REPAY_STATUS_SERVICE_FEE_CHARGED => '服务费收取成功',
    ];

    // 还款状态描述映射 后台
    public static $repayStatusDesMapForAdmin = [
        self::REPAY_STATUS_INIT => '未处理',
        self::REPAY_STATUS_PROCESSING => '还款处理中',
        self::REPAY_STATUS_SUCCESS => '还款成功',
        self::REPAY_STATUS_SERVICE_FEE_CHARGED => '服务费收取成功',
        self::REPAY_STATUS_FAILED => '还款失败',
    ];

}
