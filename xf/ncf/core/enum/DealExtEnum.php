<?php
namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealExtEnum extends AbstractEnum {

    // 手续费收费方式
    const FEE_RATE_TYPE_BEFORE = 1; // 前收
    const FEE_RATE_TYPE_BEHIND = 2; // 后收
    const FEE_RATE_TYPE_PERIOD = 3; // 分期收
    const FEE_RATE_TYPE_PROXY  = 4; // 代销分期
    const FEE_RATE_TYPE_FIXED_BEFORE = 5; // 固定比例前收
    const FEE_RATE_TYPE_FIXED_BEHIND = 6; // 固定比例后收
    const FEE_RATE_TYPE_FIXED_PERIOD = 7; // 固定比例分期

    static public $fee_rate_type_name_map = array(
        self::FEE_RATE_TYPE_BEFORE => '前收',
        self::FEE_RATE_TYPE_BEHIND => '后收',
        self::FEE_RATE_TYPE_PERIOD => '分期收',
        self::FEE_RATE_TYPE_PROXY  => '代销分期',
        self::FEE_RATE_TYPE_FIXED_BEFORE => '固定比例前收',
        self::FEE_RATE_TYPE_FIXED_BEHIND => '固定比例后收',
        self::FEE_RATE_TYPE_FIXED_PERIOD => '固定比例分期收',
    );


    const ROLLTYPE_BORROWER = 1;
    const ROLLTYPE_LOANER = 2;

    static $rollDesc = array(
        self::ROLLTYPE_BORROWER => '借款人',
        self::ROLLTYPE_LOANER => '投标人',
    );

    const LOAN_TYPE_DIRECT_LOAN = 0; // 直接放款
    const LOAN_TYPE_LATER_LOAN  = 1; // 先计息后放款
    const LOAN_AFTER_CHARGE  = 2; // 收费后放款

    static $loantypeDescCn = array(
        self::LOAN_TYPE_DIRECT_LOAN => '直接放款',
        self::LOAN_TYPE_LATER_LOAN  => '先计息后放款',
    );

    static $loantypeDesc = array(
        self::LOAN_TYPE_DIRECT_LOAN => '直接放款',
        self::LOAN_TYPE_LATER_LOAN  => '先计息后放款',
        self::LOAN_AFTER_CHARGE  => '收费后放款',
    );
}
