<?php
/**
 * 黄金提金event
 *
 */

namespace core\tmevent\gold;


use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\GoldDeliverService;


class DeliverEvent extends GlobalTransactionEvent {

    private $service;
    private $params = array();

    public function __construct($params){
        $this->params = $params;
        $this->service = new GoldDeliverService();
    }

    public function execute(){
        return $this->service->deliverEvent($this->params);
    }
}
