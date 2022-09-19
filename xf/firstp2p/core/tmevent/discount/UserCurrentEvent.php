<?php
/**
 * 黄金用户操作event
 * Date: 2017-05-22
 */

namespace core\tmevent\discount;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\GoldBidDiscountService;
use core\service\GoldBidRebateService;
use NCFGroup\Protos\Gold\Enum\CommonEnum;

class UserCurrentEvent extends GlobalTransactionEvent {

    private $service;
    private $params = array();

    public function __construct($params){
        $this->params = $params;
        if (isset($params['type']) && $params['type'] == CommonEnum::GOLD_REBATE_TYPE_ID) {
            $this->service = new GoldBidRebateService();
        } else {
            $this->service = new GoldBidDiscountService();
        }
    }

    //黄金转账
    public function execute(){
        return $this->service->userEvent($this->params);
    }

}