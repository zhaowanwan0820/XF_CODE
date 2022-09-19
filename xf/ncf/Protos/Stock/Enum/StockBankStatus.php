<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class StocBankStatus extends AbstractEnum
{
    const INIT = 0; //审核中；
    const FAIL = 2; //开户失败；
    const SUCC = 1; //开户成功；
}
