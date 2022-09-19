<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountLogService;

class AccountLog extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $result = (new AccountLogService())->getAccountDetail($param);
        $this->json_data = $result;
    }

}
