<?php
/**
 * 提金用户操作event
 */

namespace core\tmevent\gold;


use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\GoldDeliverService;


class UserDeliverEvent extends GlobalTransactionEvent {

    private $service;
    private $params = array();

    public function __construct($params){
        $this->params = $params;
        $this->service = new GoldDeliverService();
    }

    //冻结资金，余额划转
    public function execute(){
        return $this->service->userDeliverEvent($this->params);
    }

    public function rollback()
    {
        return $this->service->userDeliverRollbackEvent($this->params);
    }
}

