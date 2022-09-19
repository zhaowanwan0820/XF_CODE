<?php
namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

//幂等表常量
class IdemportentEnum extends AbstractEnum {

    //类型
    const TYPE_SPEEDLOAN_REPAY_APPLY = 'SL_REPAY_APPLY';

    //状态
    const STATUS_REPAY_FREEZE = 0;
    const STATUS_REPAY_UNFREEZE = 1;

}
