<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CreditLoanEnum extends AbstractEnum
{
    const ALARM_KEY = 'CREDITLOAN_APPLY';

    const STATUS_APPLY = 0; //申请中
    const STATUS_FAIL  = 1; //申请失败
    const STATUS_USING = 2; //使用中
    const STATUS_REPAY = 3; //还款中
    const STATUS_REPAY_HANDLE = 4; // 还款已受理
    const STATUS_PAYMENT = 6; // 支付回调
    const STATUS_FINISH = 5; //还款完成



    const BANK_ACCEPT = 1; //申请已受理
    const BANK_LOAN_SUCCESS = 2; // 银行放款成功
    const BANK_LOAN_FAIL = 4 ;// 银行放款失败
    const BANK_REPAY_SUCCESS = 3; // 还款成功
    const BANK_REPAY_FAIL = 6; // 还款失败
    const BANK_REFUSE = 8; // 受理失败
}
