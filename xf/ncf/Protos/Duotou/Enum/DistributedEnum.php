<?php
namespace NCFGroup\Protos\Duotou\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DistributedEnum extends AbstractEnum {
    const CENTRAL_STATUS_BEGIN = 0; // 开始执行
    const CENTRAL_STATUS_FINISH = 1; // 已完成

    const STEP_STATUS_INIT = 0; // 初始化完成
    const STEP_STATUS_BEGIN = 1; // 开始执行
    const STEP_STATUS_FINISH = 2; // 已完成
}
