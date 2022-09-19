<?php

namespace NCFGroup\Protos\Duotou\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class JobsEnum extends AbstractEnum {
    const JOBS_STATUS_WAITING = 0;//未执行
    const JOBS_STATUS_PROCESS = 1;//执行中
    const JOBS_STATUS_SUCCESS = 2;//执行成功
    const JOBS_STATUS_FAILED  = 3;//执行失败

    const JOBS_GET_COUNT = 500;//每次要取jobs的数量
}
