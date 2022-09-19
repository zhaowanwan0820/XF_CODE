<?php
/**
 * 黄金投资event
 * Date: 2017-05-22 17:13
 */

namespace core\tmevent\gold;


use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\GoldBidService;


class BidEvent extends GlobalTransactionEvent {

    private $service;
    private $params = array();

    public function __construct($params){
        $this->params = $params;
        $this->service = new GoldBidService();
    }

    public function execute(){
        return $this->service->bidEvent($this->params);
    }
}