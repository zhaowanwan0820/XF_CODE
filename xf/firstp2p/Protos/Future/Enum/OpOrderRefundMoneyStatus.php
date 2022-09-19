<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class OpOrderRefundMoneyStatus extends AbstractEnum
{
    const INIT = 0;                 // 初始
    const DEDUCTSUCC = 1;           // 扣除成功
    const DEDUCTFAIL = 2;           // 扣除失败
}
