<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class StockAccountStatus extends AbstractEnum
{
    const INIT = 0; //申请审核中 
    const CIFSUCC = 1;  //申请正提交
    const FAIL = 2; //终止开户
    const SUCC = 3; //开户成功
}
