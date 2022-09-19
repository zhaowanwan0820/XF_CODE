<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MoneyRecordType extends AbstractEnum
{
    const FREEZE = 0; // 冻结
    const UNFREE = 1; // 解冻退款
    const DEDUCT = 2; // 解冻扣款
    const ADD    = 3; // 加款
}
