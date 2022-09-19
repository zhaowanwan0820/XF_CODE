<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\deal\DealLoanRepayCalendarService;

class GetSumByYearMonth extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $userId = $param['userId'];
        $year = $param['year'];
        $type = $param['type'] ?: 'api';

        $result = (new DealLoanRepayCalendarService())->getSumByYearMonth($userId, $year);

        $this->json_data = $result;
    }

}
