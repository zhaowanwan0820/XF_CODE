<?php

namespace NCFGroup\Protos\Gold\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class JobsEnum extends AbstractEnum {
    const JOBS_STATUS_WAITING = 0;//未执行
    const JOBS_STATUS_PROCESS = 1;//执行中
    const JOBS_STATUS_SUCCESS = 2;//执行成功
    const JOBS_STATUS_FAILED  = 3;//执行失败

    const JOBS_GET_COUNT = 500;//每次要取jobs的数量

    const JOBS_PRIORITY_REPAY = 1; //gold还款jobs优先级
    const JOBS__PRIORITY_REPAY_LOAD = 2; //偿还用户本金和利息
    const JOBS_PRIORITY_REPAYINTEREST = 3; //付息账户支付利息
    const JOBS_PRIORITY_REPAY_COMPLETE = 4; //付款完成收尾工作
    const JOBS_PRIORITY_CURRENT_PAY_COUPON = 5; //活期返利结算
    const JOBS_PRIORITY_CURRENT_PAY_INTEREST = 6; //活期黄金收益


}
