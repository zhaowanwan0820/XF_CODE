<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\TaskService;

use core\dao\ReservationCacheModel;

// 记录随心约放款缓存
class ReserveDealLoansCacheEvent extends BaseEvent {
    private $_deal_id;

    public function __construct($deal_id) {
        $this->_deal_id = $deal_id;
    }

    public function execute() {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, sprintf("dealId: %d", $this->_deal_id))));
        ReservationCacheModel::instance()->setReserveDealLoansCache($this->_deal_id);
        return true;
    }

    public function alertMails() {
        return array('weiwei12@ucfgroup.com');
    }
}
