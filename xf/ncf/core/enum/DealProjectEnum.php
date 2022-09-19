<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealProjectEnum extends AbstractEnum {



    const CARD_TYPE_PRIVATE = 0; //0对私(对应存管是2)
    const CARD_TYPE_PUBLIC = 1; //1对公(对应存管是1)

    // 放款方式
    const LOAN_MONEY_TYPE_REAL =1; //实际放款
    const LOAN_MONEY_TYPE_FAKE = 2;// 非实际放款
    const LOAN_MONEY_TYPE_ENTRUST = 3; // 受托支付

    /**
     * 项目业务状态
     *
     * @var string
     **/
    public static $PROJECT_BUSINESS_STATUS = array(
        'cancel_loan' => -1, // 取消放款
        'waitting' => 0, //待上线
        'process' => 1, //募集中
        'full_audit' => 2, //满标待审核
        'transfer_sign' => 3, //转让签署中
        'transfer_loans_audit' => 4, //转让放款待审核
        'repaying' => 5, //还款中
        'during_repay' => 6, //正在还款
        'repaid' => 7, //已还款
    );

    public static $PROJECT_BUSINESS_STATUS_MAP = array(
        -1 => '取消放款',
        0 => '待上线',
        1 => '募集中',
        2 => '满标待审核',
        3 => '转让签署中',
        4 => '转让放款待审核',
        5 => '还款中',
        6 => '正在还款',
        7 => '已还款',
    );
}
