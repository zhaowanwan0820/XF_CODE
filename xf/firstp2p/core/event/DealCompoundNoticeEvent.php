<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\service\DealCompoundService;

require_once APP_ROOT_PATH . 'system/libs/msgcenter.php';

class DealCompoundNoticeEvent extends BaseEvent {
    private $_deal_id;
    private $_repay_money;
    private $_is_finish;

    public function __construct($deal_id, $repay_money, $is_finish) {
        $this->_deal_id = $deal_id;
        $this->_repay_money = $repay_money;
        $this->_is_finish = $is_finish;
    }

    public function execute() {
        $dc_service = new DealCompoundService();
        $dc_service->repayNotice($this->_deal_id, $this->_repay_money, $this->_is_finish);

        return true;
    }

    public function alertMails() {
        return array('wangjiantong@ucfgroup.com');
    }
}
