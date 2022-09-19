<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtEntranceService;

class GetEntranceInfo extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $activityId = $param['activityId'];
        $dtEntranceService = new DtEntranceService();
        $this->json_data = $dtEntranceService->getEntranceInfo($activityId);
    }
}
