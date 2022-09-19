<?php

namespace core\service\ncfph;

use libs\utils\Logger;
use NCFGroup\Common\Library\ApiService;

class ReserveCardService
{
    public function getReserveCardList($limit, $offset, $userId)
    {
        $params = compact('limit', 'offset', 'userId');
        return ApiService::rpc("ncfph", "reserve/CardList", $params);
    }
}
