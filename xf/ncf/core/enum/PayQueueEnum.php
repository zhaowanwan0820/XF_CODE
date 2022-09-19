<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class PayQueueEnum extends AbstractEnum {

    /**
     * 支付队列业务类型-还款(DealRepayModel::repay)
     * @var int
     */
    const BIZTYPE_REPAY = 1;

    /**
     * 支付队列业务类型-红包
     * @var int
     */
    const BIZTYPE_BONUS = 2;

    /**
     * 支付队列业务类型-返利(CouponLogService::payOut)
     * @var int
     */
    const BIZTYPE_REBATE = 3;

    /**
     * 支付队列业务类型-放款(DealPrepayService::prepay)
     * @var int
     */
    const BIZTYPE_PREPAY = 4;

    /**
     * 支付队列业务类型-转账(TransferService::_sync)
     * @var int
     */
    const BIZTYPE_TRANSFER = 5;

    /**
     * 支付队列业务类型-即付投资(DealService::transferBidJF)
     * @var int
     */
    const BIZTYPE_JF_DEAL = 6;

    /**
     * 支付队列业务类型-即付回款(DealService::transferRepayJF)
     * @var int
     */
    const BIZTYPE_JF_REPAY = 7;

    /**
     * 支付队列业务类型-易宝充值(YeepayPaymentService::_transferYeepayPayerMoney)
     * @var int
     */
    const BIZTYPE_YEEPAY_CHARGE = 8;

    /**
     * 支付队列业务类型-多投宝申购
     * @var int
     */
    const BIZTYPE_DUOTOU_BUY = 11;

    /**
     * 支付队列业务类型-多投宝赎回
     * @var int
     */
    const BIZTYPE_DUOTOU_REDEEM = 12;

    /**
     * 支付队列业务类型-多投宝付息
     * @var int
     */
    const BIZTYPE_DUOTOU_PAY_INTEREST = 13;

    /**
     * 支付队列业务类型-多投宝结息
     * @var int
     */
    const BIZTYPE_DUOTOU_SETTLE_INTEREST = 14;

    /**
     * 支付队列业务类型-多投宝收取管理费
     * @var int
     */
    const BIZTYPE_DUOTOU_FEE = 15;

    /**
     * 支付队列业务类型-第三方转账
     * @var int
     */
    const BIZTYPE_THIRD_TRANSFER = 16;

    /**
     * 支付队列业务类型 速贷收取服务费
     */
    const BIZTYPE_CREDIT_LOAN_SERVICE_FEE = 20;

    /**
     *
     * 支付队列业务类型
     * 需要同步p2p的core/dao/FinanceQueueModel
     *
     *
     */
}
