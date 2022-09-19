<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class OpOrderStatus extends AbstractEnum
{
    const INIT = 0; // 初始
    const ACPT = 1; // 授理
    const SUCC = 2; // 成功
    const FAIL = 3; // 失败
    const TFAIL = 4; // 技术失败
}
