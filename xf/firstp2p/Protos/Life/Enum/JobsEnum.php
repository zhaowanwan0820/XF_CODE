<?php

namespace NCFGroup\Protos\Life\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class JobsEnum extends AbstractEnum {
    const JOBS_STATUS_WAITING   = 0; // 未执行
    const JOBS_STATUS_PROCESS   = 1; // 执行中
    const JOBS_STATUS_SUCCESS   = 2; // 执行成功
    const JOBS_STATUS_FAILED    = 3; // 执行失败
    const JOBS_STATUS_TERMINATE = 4; // 后台强制结束
    const JOBS_GET_COUNT = 500; // 每次要取jobs的数量

    const ERRORCODE_NEEDDELAY = 1005;
    const ERRORMSG_NEEDDELAY = "Jobs Need To Be Back";

    /******************** JOBS 优先级配置 **************************/
    const JOBS_PRIORITY_NORMAL          = 100; // 通用优先级
    const JOBS_PRIORITY_TRIP            = 1000; // 网信出行相关
    const JOBS_PRIORITY_TRIP_AA         = 1001; // AA出行相关
    const JOBS_PRIORITY_PAYMENT         = 2000; // 收银台相关
    const JOBS_PRIORITY_PAYMENT_MONEY   = 2001; // 收银台代扣相关
    const JOBS_PRIORITY_PAYMENT_COMPENSATE = 2002; // 收银台订单补单相关

    // JOBS 优先级配置列表
    public static $jobsPriorityList = [
        self::JOBS_PRIORITY_TRIP => 1,
        self::JOBS_PRIORITY_TRIP_AA => 1,
        self::JOBS_PRIORITY_PAYMENT => 1,
        self::JOBS_PRIORITY_PAYMENT_MONEY => 1,
        self::JOBS_PRIORITY_PAYMENT_COMPENSATE => 1,
    ];

    // JOBS映射业务名称
    public static $jobsMapping = array(
        '\NCFGroup\Life\Daos\WxcxUserInvoiceDAO::requestThirdInvoice' => '出行-开发票',
        '\NCFGroup\Life\Core\Tools::thirdApiNotify' => '收银台-通知第三方业务',
        '\NCFGroup\Life\Services\RefundOrderService::requestUcfRefund' => '收银台-请求支付退款',
        '\NCFGroup\Life\Services\RefundOrderService::thirdBusinessRefund' => '收银台-退款通知第三方业务',
        '\NCFGroup\Life\Services\RefundOrderService::addTripRefundJob' => '出行-重新发起一笔退款',
        '\NCFGroup\Life\Services\RefundOrderService::addRefundNotifyJob' => '收银台-异步回调收银台退款结果',
        '\NCFGroup\Life\Daos\PaymentUserOrderDAO::requestBusiness' => '收银台-代扣通知第三方业务',
        '\NCFGroup\Life\Daos\PaymentUserOrderDAO::requestNoPasswordPay' => '收银台-请求支付快捷免密支付',
        '\NCFGroup\Life\Daos\PaymentUserOrderDAO::paymentOrderForSystem' => '收银台-后台系统快捷免密扣款',
        '\NCFGroup\Life\Daos\PaymentUserOrderDAO::addPayNotifyJob' => '收银台-异步回调收银台扣款结果',
        '\NCFGroup\Life\Daos\PaymentOrderDivisionDAO::addDivisionNotifyJob' => '收银台-异步回调收银台分账结果',
        '\NCFGroup\Life\Daos\WxcxUserTripDAO::createBusinessOrderForSystem' => '出行-发起后台系统快捷免密扣款',
        '\NCFGroup\Life\Daos\WxcxUserTripDAO::addSendBonusJob' => '出行-系统发送红包',
    );
}