<?php
namespace NCFGroup\Protos\Life\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Protos\Life\Enum\ErrorCode;

class PaymentEnum extends AbstractEnum {
    // 状态-已受理
    const ORDER_STATUS_ACCEPT    = 0;
    // 状态-成功
    const ORDER_STATUS_SUCCESS   = 1;
    // 状态-失败
    const ORDER_STATUS_FAILED    = 2;
    // 状态-处理中
    const ORDER_STATUS_ING       = 3;
    // 状态-交易关闭
    const ORDER_STATUS_CLOSE     = 4;
    // 状态-已请求支付系统
    const ORDER_STATUS_REQUEST   = 5;

    public static $orderStatusList = array(
        ErrorCode::RESPONSE_SUCCESS    => self::ORDER_STATUS_SUCCESS,
        ErrorCode::RESPONSE_FAILURE    => self::ORDER_STATUS_FAILED,
        ErrorCode::RESPONSE_PROCESSING => self::ORDER_STATUS_ING,
    );

    public static $paymentStatusList = array(
        self::ORDER_STATUS_ACCEPT  => ErrorCode::RESPONSE_PROCESSING,
        self::ORDER_STATUS_SUCCESS => ErrorCode::RESPONSE_SUCCESS,
        self::ORDER_STATUS_FAILED  => ErrorCode::RESPONSE_FAILURE,
        self::ORDER_STATUS_ING     => ErrorCode::RESPONSE_PROCESSING,
        self::ORDER_STATUS_CLOSE   => ErrorCode::RESPONSE_FAILURE,
        self::ORDER_STATUS_REQUEST => ErrorCode::RESPONSE_PROCESSING,
    );

    public static $paymentStatusMap = array(
        self::ORDER_STATUS_ACCEPT  => '待支付',
        self::ORDER_STATUS_SUCCESS => '支付成功',
        self::ORDER_STATUS_FAILED  => '支付失败',
        self::ORDER_STATUS_ING     => '处理中',
        self::ORDER_STATUS_CLOSE   => '交易关闭',
        self::ORDER_STATUS_REQUEST => '处理中',
    );

    // 收银台-成功、处理中、已请求的状态列表
    public static $hasDisposeOrderStatusList = [
        self::ORDER_STATUS_SUCCESS,
        self::ORDER_STATUS_ING,
        self::ORDER_STATUS_REQUEST,
    ];

    // 收银台-成功、失败、处理中、已请求的状态列表
    public static $finalOrderStatusList = [
        self::ORDER_STATUS_SUCCESS,
        self::ORDER_STATUS_FAILED,
        self::ORDER_STATUS_ING,
        self::ORDER_STATUS_REQUEST,
    ];

    // 收银台-成功、失败
    public static $endOrderStatusList = [
        self::ORDER_STATUS_SUCCESS,
        self::ORDER_STATUS_FAILED,
    ];

    // 状态-网信已受理
    const REFUND_STATUS_NEW     = 0;
    // 状态-成功
    const REFUND_STATUS_SUCCESS = 1;
    // 状态-失败
    const REFUND_STATUS_FAILED  = 2;
    // 状态-处理中
    const REFUND_STATUS_ING     = 3;

    public static $refundStatusList = array(
        ErrorCode::RESPONSE_SUCCESS    => self::REFUND_STATUS_SUCCESS,
        ErrorCode::RESPONSE_FAILURE    => self::REFUND_STATUS_FAILED,
        ErrorCode::RESPONSE_PROCESSING => self::REFUND_STATUS_ING,
    );

    public static $refundStatusMap = array(
        self::REFUND_STATUS_NEW     => '已受理',
        self::REFUND_STATUS_SUCCESS => '成功',
        self::REFUND_STATUS_FAILED  => '失败',
        self::REFUND_STATUS_ING     => '处理中',
    );

    // 绑卡成功后，页面显示提示
    const BINDCARD_SUCCESS_TIPS = '尾号%s的%s已绑定为您的消费卡';

    // 付款方式列表页，绑定理财卡的页面提示
    const PAYMENT_CARDLIST_P2P_TIPS = '升级理财卡（%s%s%s）支付';

    // 订单取消收费页面，付款方式的页面提示
    const ORDER_CANCEL_PAY_TIPS = '%s%s（尾号%s）';

    // 回调状态-未回调
    const IS_NOTIFY_NO  = 0;
    // 回调状态-已回调
    const IS_NOTIFY_YES = 1;

    // 银行卡状态-不可用
    const CARD_STATUS_UNAVAILABLE = 0;
    // 银行卡状态-可用
    const CARD_STATUS_AVAILABLE = 1;
    // 银行卡状态-未删除
    const CARD_STATUS_NOT_DELETE = 0;
    // 银行卡状态-已删除
    const CARD_STATUS_IS_DELETE = 1;

    // 银行卡类型-不限制
    const CARD_TYPE_ALL = 0;
    // 银行卡类型-储蓄卡
    const CARD_TYPE_DEPOSIT = 1;
    // 银行卡类型-信用卡
    const CARD_TYPE_CREDIT = 2;

    /**
     * 银行卡类型列表配置
     * @var array
     */
    public static $cardTypeConfig = [
        self::CARD_TYPE_CREDIT  => '信用卡',
        self::CARD_TYPE_DEPOSIT => '储蓄卡',
    ];

    // 银行卡用途-仅消费
    const CARD_STATUS_CONSUME = 1;
    // 银行卡用途-仅理财充值提现
    const CARD_STATUS_FINANCE = 2;
    // 银行卡用途-消费跟充值提现
    const CARD_STATUS_ALL = 3;
    public static $cardPurposeConfig = [
        self::CARD_STATUS_CONSUME => '消费',
        self::CARD_STATUS_FINANCE => '理财',
        self::CARD_STATUS_ALL => '消费,理财',
    ];

    // 理财绑定的银行卡
    const CARD_FLAG_P2P = 1;
    // 生活绑定的银行卡
    const CARD_FLAG_LIFE = 2;

    // 先锋支付扣款类型-用户主动支付
    const PAY_TYPE_USER   = 1;
    // 先锋支付扣款类型-后台代扣
    const PAY_TYPE_SYSTEM = 2;

    // 分账状态-待分账
    const DIVISION_WAIT    = 0;
    // 分账状态-成功
    const DIVISION_SUCCESS = 1;
    // 分账状态-失败
    const DIVISION_FAILED  = 2;
    // 分账状态-处理中
    const DIVISION_ING     = 3;
    // 分账状态-已请求
    const DIVISION_REQUEST = 4;

    // 分账状态列表
    public static $divisionStatusList = [
        ErrorCode::RESPONSE_SUCCESS    => self::DIVISION_SUCCESS,
        ErrorCode::RESPONSE_FAILURE    => self::DIVISION_FAILED,
        ErrorCode::RESPONSE_PROCESSING => self::DIVISION_ING,
    ];

    // 商户状态
    const MERCHANT_STATUS_AVAILABLE = 1;
    const MERCHANT_STATUS_UNABAILABLE = 0;

    // 版块支付方式-绑卡支付
    const PAY_FLAG_BINDCARD = 1;
    // 版块支付方式-余额支付
    const PAY_FLAG_BALANCE  = 2;
    public static $payFlagConfig = [
        self::PAY_FLAG_BINDCARD  => '绑卡支付',
    ];

    // 绑卡状态-成功
    const CARD_BIND_SUCCESS = 1;
    // 绑卡状态-失败
    const CARD_BIND_FAILED  = 2;
    // 绑卡状态-处理中
    const CARD_BIND_PROCESS = 3;

    // 退款金额加减锁
    const REFUND_MONEY_LOCK = 1;
    const REFUND_MONEY_UNLOCK = 0;

    // 业务类型-快捷免密支付业务
    const BUSINESS_TYPE_PAYMENT = 1;
    // 业务类型-退款业务
    const BUSINESS_TYPE_REFUND = 2;
    // 业务类型-分账业务
    const BUSINESS_TYPE_DIVISION = 3;
    public static $businessTypeConfig = [
        self::BUSINESS_TYPE_PAYMENT  => '快捷免密支付',
        self::BUSINESS_TYPE_REFUND   => '退款',
        self::BUSINESS_TYPE_DIVISION => '分账',
    ];

}