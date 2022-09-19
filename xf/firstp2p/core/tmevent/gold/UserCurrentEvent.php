<?php
/**
 * 黄金用户操作event
 * Date: 2017-05-22
 */

namespace core\tmevent\gold;


use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\GoldBidCurrentService;
use core\service\GoldBidEventService;
use NCFGroup\Protos\Gold\Enum\CommonEnum;


class UserCurrentEvent extends GlobalTransactionEvent {

    private $service;
    private $params = array();

    public function __construct($params){
        $this->params = $params;
        if (isset($params['type']) && ($params['type'] == CommonEnum::GOLD_EVENT_TYPE1_ID || $params['type'] == CommonEnum::GOLD_EVENT_TYPE2_ID)) {
            $this->service = new GoldBidEventService();
        } else {
            $this->service = new GoldBidCurrentService();
        }
    }

    //黄金转账
    public function execute(){
        return $this->service->userEvent($this->params);
    }

}