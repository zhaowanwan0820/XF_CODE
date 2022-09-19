<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtStatsService;
use libs\utils\Logger;

class GetP2pStats extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $p2pDealId = $param['p2pDealId'];
        $ds = new DtStatsService();
        $this->json_data = $ds->getP2pStats($p2pDealId);
    }
}
