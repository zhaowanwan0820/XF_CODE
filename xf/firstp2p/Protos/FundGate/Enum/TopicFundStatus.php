<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
class TopicFundStatus extends AbstractEnum
{
    const ALL = -1;//专题中全部基金
    const ONLINE = 0;//专题中基金上线状态
    const OFFLINE = 1;//专题中基金下线状态
}
