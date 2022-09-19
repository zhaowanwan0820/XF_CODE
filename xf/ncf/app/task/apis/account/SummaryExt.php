<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;

class SummaryExt extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];

        $result = (new AccountService())->getUserSummaryExt($userId);
        $this->json_data = $result;
    }

}
