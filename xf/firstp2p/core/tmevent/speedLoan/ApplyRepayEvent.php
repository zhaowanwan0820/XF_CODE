<?php
/**
 * 在线申请还款
 * Date: 2017-05-22 17:13
 */

namespace core\tmevent\speedLoan;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\speedLoan\RepayService;

class ApplyRepayEvent extends GlobalTransactionEvent {

    //还款申请参数
    public $params;

    public function __construct($params){
        $this->params = $params;
    }

    public function execute(){
        $service = new RepayService();
        $result = $service->repayApply($this->params);
        return (bool) $result['data'];
    }
}
