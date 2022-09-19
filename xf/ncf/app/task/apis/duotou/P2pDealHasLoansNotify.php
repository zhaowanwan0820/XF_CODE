<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtDealService;

class P2pDealHasLoansNotify extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $deal_id = $param['dealId'];
        $service = new DtDealService();
        $this->json_data = $service->p2pDealHasLoansNotify($deal_id);
    }
}
