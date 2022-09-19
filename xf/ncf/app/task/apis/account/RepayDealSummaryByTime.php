<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\deal\DealLoanRepayService;

class RepayDealSummaryByTime extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];
        $time = $param['time'];

        $result = (new DealLoanRepayService())->getRepayDealSumaryByTime($userId, $time);
        $this->json_data = $result;
    }

}