<?php

namespace core\enum\contract;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ContractSignSwitchEnum extends AbstractEnum
{
    /**
     * 开关状态
     */
    const STATUS_CLOSED     = 0; //关闭
    const STATUS_OPENDED    = 1;//打开

    const TYPE_BORROW       = 1; //借款人
    const TYPE_AGENCY       = 2;//担保方
    const TYPE_ADVISORY     = 3;//资产管理方
}
