<?php
namespace NCFGroup\Protos\Ptp\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class HouseEnum extends AbstractEnum {
    // 房贷审核状态
    const STATUS_CHECKING = 1;              // 审核中
    const STATUS_FIRST_CHECK_PASSED = 2;    // 初审通过
    const STATUS_FIRST_CHECK_FAILED = 3;    // 初审未通过
    const STATUS_FACE_CHECK_PASSED = 4;     // 面审通过
    const STATUS_FACE_CHECK_FAILED = 5;     // 面审未通过
    const STATUS_MAKING_LOAN = 6;           // 放款中
    const STATUS_USING = 7;                 // 使用中
    const STATUS_FINISHED = 8;              // 正常结清
    const STATUS_FINISH_OVERDUE = 9;        // 逾期结清
    const STATUS_FINISH_AHEAD = 10;         // 提前结清
    const STATUS_OVERDUE = 11;              // 逾期中

    const REPAYMENT_INTEREST_MONTH_WITHOUT_PRINCIPAL = 1;
    const REPAYMENT_INTEREST_MONTH_WITH_PRINCIPAL = 2;

    // 合作机构
    const COOPERATION_YI_FANG = 1;          // 一房

    // 业务类型
    const TYPE_NCF_HOUSE = 1;               // 网信房贷

    // 是否开通房贷业务
    const STATUS_IS_ON = 1;                 // 开通
    const STATUS_IS_OFF = 0;                // 关闭

    // 还款计划状态
    const STATUS_REPAY_PLAN_NOT = 0;                // 未还
    const STATUS_REPAY_PLAN_NORMAL_FINISH = 1;      // 正常结清
    const STATUS_REPAY_PLAN_OVERDUE = 2;            // 逾期中
    const STATUS_REPAY_PLAN_OVERDUE_FINISH = 3;     // 逾期结清
    const STATUS_REPAY_PLAN_AHEAD_FINISH = 4;           // 提前还款

    // 房贷状态
    public static $STATUS = array(
        self::STATUS_CHECKING => '审核中',
        self::STATUS_FIRST_CHECK_PASSED => '初审通过',
        self::STATUS_FIRST_CHECK_FAILED => '初审未通过',
        self::STATUS_FACE_CHECK_PASSED => '面审通过',
        self::STATUS_FACE_CHECK_FAILED => '面审未通过',
        self::STATUS_MAKING_LOAN => '放款中',
        self::STATUS_USING => '使用中',
        self::STATUS_FINISHED => '已结清',
        self::STATUS_FINISH_OVERDUE => '逾期结清',
        self::STATUS_FINISH_AHEAD => '提前结清',
        self::STATUS_OVERDUE => '逾期中'
    );

    /*
     * 借款状态详细信息
     */
    public static $STATUS_TEXT = array(
        self::STATUS_CHECKING => '您的借款申请已提交，正在审核中，请您耐心等待!',
        self::STATUS_FIRST_CHECK_PASSED => '恭喜您初审通过，我们的工作人员会尽快与您联系，请您保持手机畅通!',
        self::STATUS_FIRST_CHECK_FAILED => '很遗憾您的申请没有通过，期待下一次为您服务！',
        self::STATUS_FACE_CHECK_PASSED => '恭喜您面审通过，我们的工作人员会尽快与您联系，请您保持手机畅通！',
        self::STATUS_FACE_CHECK_FAILED => '很遗憾您的申请没有通过，期待下一次为您服务！',
        self::STATUS_MAKING_LOAN => '您的借款正在放款中，请您耐心等待！',
        self::STATUS_USING => '实际状态更新可能会有延迟，请您在还款1个工作日后查询还款计划!',
        self::STATUS_FINISHED => '实际状态更新可能会有延迟，请您在还款1个工作日后查询还款计划!',
        self::STATUS_FINISH_OVERDUE => '实际状态更新可能会有延迟，请您在还款1个工作日后查询还款计划!',
        self::STATUS_FINISH_AHEAD => '实际状态更新可能会有延迟，请您在还款1个工作日后查询还款计划!',
        self::STATUS_OVERDUE => '实际状态更新可能会有延迟，请您在还款1个工作日后查询还款计划!',
    );

    // 还款方式
    public static $REPAYMENT_MODES = array(
        self::REPAYMENT_INTEREST_MONTH_WITHOUT_PRINCIPAL => '按月付息，到期还本'
    );

    // 合作机构
    public static $COOPERATION = array(
        self::COOPERATION_YI_FANG => '一房'
    );

    // 还款计划状态list
    public static $REPAY_PLAN_STATUS = array(
        self::STATUS_REPAY_PLAN_NOT => '待还款',
        self::STATUS_REPAY_PLAN_NORMAL_FINISH => '已还款',
        self::STATUS_REPAY_PLAN_OVERDUE => '逾期中',
        self::STATUS_REPAY_PLAN_OVERDUE_FINISH => '逾期结清',
        self::STATUS_REPAY_PLAN_AHEAD_FINISH => '提前还款'
    );
}
