<?php

namespace core\service\makeloans;

use core\enum\DealGrantFeeEnum;
use core\service\BaseService;
use NCFGroup\Common\Library\Idworker;
use core\dao\makeloans\DealGrantFeeModel;

/**
 * 放款提现后收费
 * Class DealGrantFeeService
 * @package core\service\makeloans
 */

class DealGrantFeeService extends BaseService {

    public function dealGrantFeeRequest($orderId,$dealId){

    }


    /**
     * 取得所有超时未关单的订单
     * @return \libs\db\Model
     */
    public function getOverTimeList(){
        $m = new DealGrantFeeModel();
        $t = DealGrantFeeEnum::OVER_TIME_SECONDS;

        $statusStr = DealGrantFeeEnum::STATUS_FEE_SUCC . "," . DealGrantFeeEnum::STATUS_FEE_FAIL . "," . DealGrantFeeEnum::STATUS_FEE_OVERTIME;
        $cond = 'status not in ('.$statusStr.') AND request_time >'.time() - $t;
        return $m->findAllViaSlave($cond);
    }
}

