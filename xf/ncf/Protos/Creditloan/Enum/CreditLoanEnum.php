<?php
namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;

class CreditLoanEnum extends AbstractEnum {

    //借款类型
    const LOAN_TYPE_NORMAL = 1; //借款
    const LOAN_TYPE_CONSUME = 2; //消费

    //借款方式
    const LOAN_MODE_ONLINE = 1; //线上
    const LOAN_MODE_OFFLINE = 2; //线下

    //借款状态
    const LOAN_STATUS_APPLY = 1; //申请中
    const LOAN_STATUS_WAITING = 2; //已受理
    const LOAN_STATUS_SUCESS = 3; //使用中
    const LOAN_STATUS_FAIL = 4; //审核失败
    const LOAN_STATUS_CANCEL = 5; //借款取消中
    const LOAN_STATUS_CLOSE = 6; //已取消
    const LOAN_STATUS_AUDIT_FAIL = 7;//审核失败（系统取消借款）
    const LOAN_STATUS_FINISH = 8;//已结清
    const LOAN_STATUS_REPAY = 9;//还款中

    const DEAL_TPL_TYPE_HAIKOU = 1; // ?????
    //资金提供方
    const LOAN_PROVIDER_HAIKOU = 1; //海口银行
    const LOAN_PROVIDER_WANGXIN = 2; //网信

    // 还款方式配置
    public static $repaymentMethodMap = [
        CreditEnum::REPAYMENT_METHOD_EQUAL_PRINCIPAL => 'EQUAL_PRINCIPAL',
        CreditEnum::REPAYMENT_METHOD_EQUAL_PRINCIPAL_INTEREST => 'EQUAL_PRINCIPAL_INTEREST',
        CreditEnum::REPAYMENT_METHOD_PRINCIPAL_INTEREST => 'PRINCIPAL_INTEREST',
        CreditEnum::REPAYMENT_METHOD_ONE_TIME_PRINCIPAL_INTEREST => 'ONE_TIME_PRINCIPAL_INTEREST',
    ];

    public static $repaymentMethodDescMap = [
        CreditEnum::REPAYMENT_METHOD_EQUAL_PRINCIPAL => '等额本金',
        CreditEnum::REPAYMENT_METHOD_EQUAL_PRINCIPAL_INTEREST => '等额本息',
        CreditEnum::REPAYMENT_METHOD_PRINCIPAL_INTEREST => '等本等息',
        CreditEnum::REPAYMENT_METHOD_ONE_TIME_PRINCIPAL_INTEREST => '一次性还本付息',
    ];

    public static $loanTypeDesMap = [
        self::LOAN_TYPE_NORMAL => '借款',
        self::LOAN_TYPE_CONSUME => '消费',
    ];

    public static $loanModeDesMap = [
        self::LOAN_MODE_ONLINE => '线上',
        self::LOAN_MODE_OFFLINE => '线下',
    ];

    public static $loanStatusDesMap = [
        self::LOAN_STATUS_APPLY => '申请中',
        self::LOAN_STATUS_WAITING => '已受理',
        self::LOAN_STATUS_SUCESS => '使用中',
        self::LOAN_STATUS_FAIL => '审批失败',
        self::LOAN_STATUS_CANCEL => '借款取消中',
        self::LOAN_STATUS_CLOSE => '已取消',
        self::LOAN_STATUS_AUDIT_FAIL => '审核失败',
        self::LOAN_STATUS_FINISH => '已结清',
        self::LOAN_STATUS_REPAY => '还款中',
    ];

    public static $loanProviderDesMap = [
        self::LOAN_PROVIDER_HAIKOU => '海口联合农商银行',
        self::LOAN_PROVIDER_WANGXIN => '网信',
    ];
}
