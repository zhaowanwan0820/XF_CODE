<?php

namespace task\apis\dealload;

use core\dao\deal\DealLoadModel;
use libs\utils\Logger;
use core\service\deal\DealService;

use task\lib\ApiAction;

class GetUserLoadMoreTenThousand extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $userId =  $params['userId'];
        $ds = new DealLoadModel();
        $dealLoadInfo = $ds->getUserLoadMoreTenThousand($userId);

        $res = !empty($dealLoadInfo) ? $dealLoadInfo->getRow() : false;

        $this->json_data = $res;
    }
}