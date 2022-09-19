<?php
namespace core\enum\duotou;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class P2pDealMappingStatsEnum extends AbstractEnum {
    const STATUS_NORMAL         = 0; // 正常状态
    const STATUS_HAS_NOTIFY     = 1; // 已发送通知

    const TYPE_INVEST           = 0; // 正常投资
    const TYPE_REDEEM           = 1; // 正常赎回
}
