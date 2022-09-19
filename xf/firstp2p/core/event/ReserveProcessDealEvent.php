<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\TaskService;

use core\service\UserReservationService;

// 预约处理标的
class ReserveProcessDealEvent extends BaseEvent {
    private $_deal_id;

    public function __construct($deal_id) {
        $this->_deal_id = $deal_id;
    }

    public function execute() {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, sprintf("dealId: %d", $this->_deal_id))));
        $userReservationService = new UserReservationService();
        return $userReservationService->processOneDeal($this->_deal_id);
    }

    public function alertMails() {
        return array('weiwei12@ucfgroup.com');
    }
}
