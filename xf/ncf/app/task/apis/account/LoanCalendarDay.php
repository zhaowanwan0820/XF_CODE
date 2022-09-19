<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\deal\DealLoanRepayCalendarService;

class LoanCalendarDay extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];
        $year = $param['year'];
        $month  = $param['month'];
        $type   = $param['type'] ?: 'api';

        $result = (new DealLoanRepayCalendarService())->getDealLoanRepayCalendar($userId, $year, $month, $type);

        $this->json_data = $result;

    }

}
