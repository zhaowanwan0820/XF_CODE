<?php

namespace task\apis\dealload;

use core\dao\deal\DealLoadModel;
use libs\utils\Logger;
use core\service\deal\DealService;

use task\lib\ApiAction;

class GetO2ODealLoadInfo extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $dealLoadId =  $params['dealLoadId'];
        $ds = new DealLoadModel();
        $o2oDealLoadInfo = $ds->getO2ODealLoadInfo($dealLoadId);
        if ($o2oDealLoadInfo['deal_id'] && $o2oDealLoadInfo['money']) {
            $o2oDealLoadInfo['annualizedAmount'] = DealService::getAnnualizedAmountByDealIdAndAmount($o2oDealLoadInfo['deal_id'], $o2oDealLoadInfo['money']);
        } else {
            $o2oDealLoadInfo['annualizedAmount'] = 0;
        }
        $this->json_data = $o2oDealLoadInfo;
    }
}
