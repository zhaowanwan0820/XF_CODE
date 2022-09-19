<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\dealload\DealLoadService;

class GetDealLoadCount extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];

        $dealLoadService = new DealLoadService();
        $data = $dealLoadService->countByUserId($userId);
        $this->json_data = $data;
    }
}