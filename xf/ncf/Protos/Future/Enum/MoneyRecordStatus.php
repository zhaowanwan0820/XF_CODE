<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MoneyRecordStatus extends AbstractEnum
{
    const INIT = 0; // 初始
    const SUCC = 1; // 成功
    const FAIL = 2; // 技术
}
