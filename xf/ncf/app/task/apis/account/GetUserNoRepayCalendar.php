<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\deal\DealLoanRepayCalendarService;

class GetUserNoRepayCalendar extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();

        $userId     = $param['userId'];
        $beginYear  = $param['beginYear'];
        $beginMonth = $param['beginMonth'];
        $beginDay   = $param['beginDay'];
        $day        = $param['day'];

        $result = (new DealLoanRepayCalendarService())->getUserNoRepayCalendar($userId,$beginYear,$beginMonth,$beginDay,$day);
        $this->json_data = $result;
    }

}
