<?php

namespace core\service\ncfph;

use libs\utils\Logger;
use NCFGroup\Common\Library\ApiService;

class ReserveEntraService
{
    public function getReserveEntraList($limit, $offset, $userId)
    {
        $params = compact('limit', 'offset', 'userId');
        return ApiService::rpc("ncfph", "reserve/EntraList", $params);
    }
}
