<?php

namespace core\tmevent\bid;

use core\tmevent\bid\BonusConsumeEvent;

class BonusGoldCurrentConsumeEvent extends BonusConsumeEvent
{
    public function execute()
    {
        //处理投资逻辑，成功返回true，失败返回false，其他结果一律会重试
        if (empty($this->accountInfo)) return true; // 没有红包直接过

        return $this->service->consumeBonusToGold($this->userId, $this->records,
            $this->useMoney, $this->orderId, time(), $this->dealName, $this->accountInfo);
    }

}
