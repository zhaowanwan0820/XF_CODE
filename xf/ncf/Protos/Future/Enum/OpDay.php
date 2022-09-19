<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class OpDay extends AbstractEnum
{
    const CURR_DAY = 0;  // 当前交易日
    const NEXT_DAY = 1;  // 下一个交易日
}
