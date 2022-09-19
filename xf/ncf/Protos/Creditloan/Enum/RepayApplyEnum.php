<?php
namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;

class RepayApplyEnum extends AbstractEnum {

    // 还款类型
    const REPAY_TYPE_BALANCE = 1; // 账户扣款
    const REPAY_TYPE_BANK = 2; // 资金代扣

    // 申请状态
    const STATUS_INIT = 1; // 发起申请
    const STATUS_PROCESSING = 2; // 还款发起中，到支付或存管
    const STATUS_PAY_PROCESSING = 3; // 资金划扣中
    const STATUS_PAY_SUCCESS = 4; // 资金划扣成功
    const STATUS_PAY_FAILED = 5; // 资金划扣失败
    const STATUS_REPAY_SUCCESS = 6; // 还款成功
    const STATUS_REFUND = 7; // 还款申请退回

    // 还款申请发起来源
    const TYPE_ONLINE = 1; // 线上用户发起还款
    const TYPE_OFFLINE = 2; // 网信回款触发还款

    // 标的类型， 非报备
    const DEAL_TYPE_NORMAL = 1;
    // 标的类型， 报备
    const DEAL_TYPE_SUPERVISION = 2;

    // 还款类型描述映射
    public static $repayTypeDesMap = [
        self::REPAY_TYPE_BALANCE => '账户扣款',
        self::REPAY_TYPE_BANK => '资金代扣',
    ];

    // 申请状态映射
    public static $statusDesMap = [
        self::STATUS_INIT => '发起申请',
        self::STATUS_PROCESSING => '还款发起中',
        self::STATUS_PAY_PROCESSING => '资金划扣发起中', // 资金划扣中
        self::STATUS_PAY_SUCCESS => '资金划扣成功',
        self::STATUS_PAY_FAILED => '资金划扣失败',
        self::STATUS_REPAY_SUCCESS => '还款成功',
        self::STATUS_REFUND => '还款申请退回',
    ];

    // 还款申请发起来源映射
    public static $typeDesMap = [
        self::TYPE_ONLINE => '线上用户发起还款',
        self::TYPE_OFFLINE => '网信回款触发还款',
    ];

}
