<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;

class Summary extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];

        $result = (new AccountService())->getUserSummary($userId);
        $this->json_data = $result;
    }

}
