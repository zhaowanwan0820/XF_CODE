<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class OpOrderType extends AbstractEnum
{
    const APPLY   = 1; // 申请配资
    const APEND   = 2; // 追加保证金
    const RENEW   = 3; // 合约续期
    const EXTRACT = 4; // 提取利润
    const CLOSE   = 5;  // 终止合约
    const DEDUCT  = 6; // 扣减管理费
}
