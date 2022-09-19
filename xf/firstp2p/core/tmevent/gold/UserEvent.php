<?php
/**
 * 黄金用户操作event
 * Date: 2017-05-22
 */

namespace core\tmevent\gold;


use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\GoldBidService;


class UserEvent extends GlobalTransactionEvent {

    private $service;
    private $params = array();

    public function __construct($params){
        $this->params = $params;
        $this->service = new GoldBidService();
    }

    //冻结资金，余额划转
    public function execute(){
        return $this->service->userEvent($this->params);
    }

    public function rollback()
    {
        return $this->service->userEventRollback($this->params);
    }
}