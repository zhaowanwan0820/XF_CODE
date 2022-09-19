<?php

namespace task\apis\dealload;

use core\dao\deal\DealLoadModel;
use libs\utils\Logger;

use task\lib\ApiAction;

class IsTodayLoadByUserId extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $userId =  $params['userId'];
        $ds = new DealLoadModel();
        $ret = $ds->isTodayLoadByUserId($userId);
        $this->json_data = $ret;
    }
}
