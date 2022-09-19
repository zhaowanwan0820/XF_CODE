<?php
namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class IfaEnum extends AbstractEnum {

    const STATUS_NONE  = 0; // 未上报
    const STATUS_SUCC  = 1; // 已上报
    const STATUS_FAIL  = 2; // 禁止上报
    const STATUS_DOING = 3; // 已通知，等待回调
    const STATUS_CALLBACK_FAIL = 4; // 回调失败



}
