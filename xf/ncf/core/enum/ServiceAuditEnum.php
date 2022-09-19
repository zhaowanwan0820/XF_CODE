<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ServiceAuditEnum extends AbstractEnum
{
    /**
     * 业务类型 红包类型
     *
     * @var int
     */
    const SERVICE_TYPE_BONUS = 1;

    /**
     * 业务类型 放款
     *
     * @var int
     */
    const SERVICE_TYPE_LOAN = 2;

    /**
     * 业务类型 劵
     *
     * @var int
     */
    const SERVICE_TYPE_COUPON = 3;

    /**
     * 业务类型 强制还款
     *
     * @var int
     */
    const SERVICE_TYPE_REPAY = 4;

    /**
     * 业务类型 提前还款
     *
     * @var int
     */
    const SERVICE_TYPE_PREPAY = 5;

    /**
     * 业务类型 专享项目放款
     *
     * @var int
     */
    const SERVICE_TYPE_PROJECT_LOAN = 6;

    /**
     * 业务类型 专享项目还款
     *
     * @var int
     */
    const SERVICE_TYPE_PROJECT_REPAY = 7;

    /**
     * 业务类型 专享项目提前还款
     *
     * @var int
     */
    const SERVICE_TYPE_PROJECT_PREPAY = 8;

    /**
     * 操作类型 新增操作
     *
     * @var string
     */
    const OPERATION_ADD  = 'add';

    /**
     * 操作类型 更新操作
     *
     * @var string
     */
    const OPERATION_SAVE = 'save';

    /**
     * 审核状态 未审核
     *
     * @var int
     */
    const NOT_AUDIT  = 1;

    /**
     * 审核状态 审核成功
     *
     * @var int
     */
    const AUDIT_SUCC = 2;

    /**
     * 审核状态 审核失败
     *
     * @var int
     */
    const AUDIT_FAIL = 3;

    /**
     * 业务审核状态
     *
     * @var array
     */
    public static $auditStatus = array(
        0 => ' 全部 ',
        1 => '待审核',
        2 => '已通过',
        3 => '未通过',
    );
}
