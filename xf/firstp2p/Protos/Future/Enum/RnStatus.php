<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class RnStatus extends AbstractEnum
{
    /**
     * 操盘
     */
    const OP_STOCK = 2;
    /**
     * 警告线
     */
    const WARING = 3;
    /**
     * 平仓线
     */
    const CLOSEOUT = 4;
}
