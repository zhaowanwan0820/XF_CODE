<?php
/**
 * 红包充值event
 * Date: 2017-05-22
 */

namespace core\tmevent\gold;


use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\GoldBidService;


class BonusEvent extends GlobalTransactionEvent {

    private $service;
    private $params = array();

    public function __construct($params){
        $this->params = $params;
        $this->service = new GoldBidService();
    }

    //红包充值
    public function execute(){
        return $this->service->bonusEvent($this->params);
    }

    //不回滚，相当于红包被套现了
    public function rollback()
    {
        return true;
    }

}