<?php

namespace task\apis\deal;

use libs\utils\Logger;
use core\service\deal\DealService;
use task\lib\ApiAction;

class GetProductNameByDealId extends ApiAction
{
    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:'.json_encode($params))));
        $dealId = intval($params['dealId']);
        $this->json_data = (new DealService())->getProductNameByDealId($dealId);
    }
}
