<?php
namespace NCFGroup\Protos\Ptp\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MoneyOrderEnum extends AbstractEnum {

    // 业务类型
    const BIZ_TYPE_GOLD = 1; //黄金
    const BIZ_TYPE_CREDIT_LOAN = 2; //速贷
    const BIZ_TYPE_BONUS = 3; // 红包

    // 资金操作类型, 和资金记录保持一致
    const OPTYPE_BALANCE = 0; // 加余额
    const OPTYPE_FREEZE = 1; // 减余额加冻结
    const OPTYPE_FREEZE_DECREASE = 2; // 扣减冻结

    // 对账方向
    const CHECK_WX_BIZ = 1; // 网信对业务
    const CHECK_BIZ_WX = 2; // 业务对网信

    // 对账状态
    const CHECK_STATUS_UNDO = 0; // 未对账(完全未对账，或对账失败)
    const CHECK_STATUS_DONE = 1; // 对账成功
    const CHECK_STATUS_IGNORE = 2; // 对账有问题，但可忽略

    // 对账错误类型
    const CHECK_ERROR_NO_ORDER = 1; // 订单不存在
    const CHECK_ERROR_ORDER_STATUS = 2; // 订单状态不匹配
    const CHECK_ERROR_AMOUNT = 3; // 订单金额不匹配
    const CHECK_ERROR_USER = 4; // 订单用户不匹配
    const CHECK_ERROR_PAYER = 5; // 付款方不匹配
    const CHECK_ERROR_RECEIVER = 6; // 收款方不匹配

    // 业务类型对应
    public static $bizTypeMap = [
        self::BIZ_TYPE_GOLD => [
            'desc' => '黄金',
            'enum' => '\NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum'
        ],
        self::BIZ_TYPE_CREDIT_LOAN => [
            'desc' => '速贷',
            'enum' => '\NCFGroup\Protos\Creditloan\Enum\MoneyOrderEnum'
        ],
        self::BIZ_TYPE_BONUS => [
            'desc' => '红包',
            'enum' => '\NCFGroup\Protos\Bonus\Enum\MoneyOrderEnum'
        ]
    ];

    // 对账状态描述
    public static $checkStatusDesc = [
        self::CHECK_STATUS_UNDO => '未对账',
        self::CHECK_STATUS_DONE => '已对账',
        self::CHECK_STATUS_IGNORE => '可忽略'
    ];

    public static $checkDirectionDesc = [
        self::CHECK_WX_BIZ => '网信对账%s',
        self::CHECK_BIZ_WX => '%s对账网信'
    ];

    public static $checkErrorDesc = [
        self::CHECK_ERROR_NO_ORDER => '订单不存在',
        self::CHECK_ERROR_ORDER_STATUS => '订单状态不匹配',
        self::CHECK_ERROR_AMOUNT => '订单金额不匹配',
        self::CHECK_ERROR_USER => '订单用户不匹配',
        self::CHECK_ERROR_PAYER => '付款方不匹配',
        self::CHECK_ERROR_RECEIVER => '收款方不匹配',
    ];
}
