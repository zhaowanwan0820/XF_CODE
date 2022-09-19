<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class OpOrderMoneyStatus extends AbstractEnum
{
    const INIT = 0;                 // 初始
    const FREEZESUCC = 1;           // 冻结成功
    const FREEZEFAIL = 2;           // 冻结失败
    const UNFREEZESUCC = 3;         // 解冻成功
    const UNFREEZEFAIL = 4;         // 解冻失败
    const DEDUCTSUCC = 5;           // 扣除成功
    const DEDUCTFAIL = 6;           // 扣除失败
}
