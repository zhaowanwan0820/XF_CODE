<?php
/**
 * 黄金变现处理event
 * Date: 2017-05-22 17:13
 */

namespace core\tmevent\gold;


use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\GoldWithdrawService;

class WithdrawProcess extends GlobalTransactionEvent {

    private $service;

    //变现单号
    public $orderId;

    public function __construct($orderId){
        $this->orderId = $orderId;
        $this->service = new GoldWithdrawService();
    }

    public function commit(){
        return $this->service->withdrawProcess($this->orderId);
    }
}
