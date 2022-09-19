<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\dao\ReservationCacheModel;

//记录随心约回款缓存
class ReserveDealRepayCacheEvent extends BaseEvent {
    private $_deal_repay_id;
    private $_deal_id;

    public function __construct($deal_repay_id, $deal_id) {
        $this->_deal_repay_id = $deal_repay_id;
        $this->_deal_id = $deal_id;
    }

    public function execute() {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, sprintf("dealId: %d, dealRepayId: %d", $this->_deal_id, $this->_deal_repay_id))));
        ReservationCacheModel::instance()->setReserveDealRepayCache($this->_deal_id, $this->_deal_repay_id);
        return true;
    }

    public function alertMails() {
        return array('weiwei12@ucfgroup.com');
    }
}
