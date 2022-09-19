<?php
/**
 * 冻结金额
 * Date: 2017-05-22 17:13
 */

namespace core\tmevent\speedLoan;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\speedLoan\RepayService;

class FreezeRepayMoneyEvent extends GlobalTransactionEvent {

    //参数
    private $params;

    public function __construct($params){
        $this->service = new RepayService();
        $this->params = $params;
    }

    public function execute() {
        return $this->service->freezeRepayMoney($this->params);
    }

    public function rollback() {
        return $this->service->unfreezeRepayMoney($this->params);
    }
}
