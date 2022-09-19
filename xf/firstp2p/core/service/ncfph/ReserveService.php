<?php

namespace core\service\ncfph;

use libs\utils\Logger;
use NCFGroup\Common\Library\ApiService;

class ReserveService
{
    public function getEffectReserveCountByUserId($userId)
    {
        $params = compact('userId');
        $count = ApiService::rpc("ncfph", "reserve/EffectReserveCount", $params);
        return (int) $count;
    }
}
