<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class BatchJobEnum extends AbstractEnum
{
    const BATCH_JOB_REPAY = 1; //还款
    const BATCH_JOB_TIMESTAMP = 2; //时间戳
    const BATCH_JOB_LOAN = 3; //放款
    const BATCH_JOB_DK = 5; //代扣

    const BATCH_JOB_STATUS_INVALID = 0; //无效
    const BATCH_JOB_STATUS_VALID = 1; //有效
    /**
     * 批作业类型
     * @var array
     */
    static $jobType = array(
        self::BATCH_JOB_REPAY => '还款',
        self::BATCH_JOB_TIMESTAMP => '时间戳',
        self::BATCH_JOB_LOAN => '放款',
        self::BATCH_JOB_DK => '代扣',
    );
}