<?php

namespace core\dao;

class LoanAccountAdjustMoneyModel extends BaseModel {

    const STATUS_NEED_AUDIT  = 1; // A角色待审核
    const STATUS_NEED_FINAL_AUDIT = 2; // B角色带审核
    const STATUS_PASS = 3; // 已通过
    const STATUS_REFUSE = 4; // 已拒绝
    const STATUS_REFUSE_A = 4; // A角色已拒绝
    const STATUS_REFUSE_B = 5; // B角色已拒绝

    const TYPE_WITHDRAW_RETURN = 1; // 提现失败充值
    const TYPE_SYSTEM_REPAIR = 2; // 系统修正

    static $loan_account_adjust_money_status = array(
        self::STATUS_NEED_AUDIT => 'A角色待审核',
        self::STATUS_NEED_FINAL_AUDIT => 'B角色待审核',
        self::STATUS_PASS  => '已通过',
        self::STATUS_REFUSE => '已拒绝',
    );


    static $loan_account_adjust_money_type = array(
        self::TYPE_WITHDRAW_RETURN => '提现失败充值',
        self::TYPE_SYSTEM_REPAIR => '系统修正',
    );

}
