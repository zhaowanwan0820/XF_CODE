<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealRepayEnum extends AbstractEnum {

    const STATUS_WAITING = 0; // 待还
    const STATUS_PAIED_ONTIME = 1; // 准时
    const STATUS_PAIED_DELAYED = 2; // 逾期
    const STATUS_PAIED_DELAYED_CRITICAL = 3; // 严重逾期
    const STATUS_PREPAID = 4; // 提前

    public static $statusMsg = array(
        self::STATUS_WAITING => '待还',
        self::STATUS_PAIED_ONTIME => '准时还款',
        self::STATUS_PAIED_DELAYED => '逾期还款',
        self::STATUS_PAIED_DELAYED_CRITICAL => '严重逾期',
        self::STATUS_PREPAID => '提前还款'
    );


    const REPAY_OP_TYPE_REPAY = 'repay'; // 还款操作类型--普通还款
    const REPAY_OP_TYPE_PREPAY = 'prepay'; // 还款操作类型--提前还款

    const DEAL_REPAY_TYPE_SELF                  = 0; // 借款人还款
    const DEAL_REPAY_TYPE_DAIDIAN               = 1; // 代垫
    const DEAL_REPAY_TYPE_DAICHANG              = 2; // 代偿
    const DEAL_REPAY_TYPE_DAICHONGZHI           = 3; // 代充值
    const DEAL_REPAY_TYPE_DAIKOU                = 4; // 代扣
    const DEAL_REPAY_TYPE_JIANJIE_DAICHANG      = 5; // 间接代偿
    const DEAL_REPAY_TYPE_PART_SELF             = 6; // 部分还款
    const DEAL_REPAY_TYPE_PART_DAICHANG         = 7; // 部分代偿
    const DEAL_REPAY_TYPE_PREPAY_DZH            = 8; // 多账户提前结清
    const DEAL_REPAY_TYPE_ZHUDONG_DAIKOU          = 9; // 代扣主动还款
    const DEAL_REPAY_TYPE_NORMAL_PART           = 10; // 非足额还款（部分还款）

    public static $repayTypeMsg = array(
        self::DEAL_REPAY_TYPE_SELF              => '借款人还款',
        self::DEAL_REPAY_TYPE_DAIDIAN           => 'b机构代偿',
        self::DEAL_REPAY_TYPE_DAICHANG          => '直接代偿',
        self::DEAL_REPAY_TYPE_DAICHONGZHI       => 'a机构代偿',
        self::DEAL_REPAY_TYPE_DAIKOU            => '代扣',
        self::DEAL_REPAY_TYPE_JIANJIE_DAICHANG  => '间接代偿',
        self::DEAL_REPAY_TYPE_PART_SELF         => '部分还款',
        self::DEAL_REPAY_TYPE_PART_DAICHANG     => '部分代偿',
        self::DEAL_REPAY_TYPE_PREPAY_DZH        => '多账户提前结清',
        self::DEAL_REPAY_TYPE_ZHUDONG_DAIKOU    => '主动还款',
        self::DEAL_REPAY_TYPE_NORMAL_PART       => '非足额还款',
    );

    const DEAL_REPAY_NORMAL = 1; // 正常还款
    const DEAL_REPAY_PREPAY = 2; // 提前还款
    const DEAL_REPAY_PART = 3; // 部分还款
    const DEAL_REPAY_PREYAY_DZH = 4; //多账户提前结清
    const DEAL_REPAY_NORMAL_PART = 5; // 部分还款

    const PREPAY_STATUS_WAITING = 0; //审核中
    const PREPAY_STATUS_PASSED = 1;  // 审核通过
    const PREPAY_STATUS_REFUSE = 3; // 拒绝
    const PREPAY_STATUS_REPAYED = 4; // 已还

    const PREPAY_AUDIT_STATUS_PASSED = 1;
}
