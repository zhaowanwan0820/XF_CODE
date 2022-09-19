<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\deal\DealLoanRepayCalendarService;

class LoanCalendarList extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $userId = $param['userId'];
        $type = $param['type'] ?: 'api';

        $result = (new DealLoanRepayCalendarService())->getDealLoanRepayCalendarList($userId, $type);

        $this->json_data = $result;
    }

}
