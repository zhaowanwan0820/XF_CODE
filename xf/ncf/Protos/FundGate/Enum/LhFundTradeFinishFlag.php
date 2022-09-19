<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class LhFundTradeFinishFlag extends AbstractEnum
{
    const LHFUND_TRADE_FINISH_EXTENDED = 1; //巨额赎回顺延
    const LHFUND_TRADE_FINISH_APPLY = 9; // 赎回申请已结束
}