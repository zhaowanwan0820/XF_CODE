<?php
/**
 * 随鑫约入口列表
 */

namespace task\apis\reserve;

use task\lib\ApiAction;
use core\enum\ReserveEntraEnum;
use core\enum\DealEnum;
use core\service\reserve\ReservationEntraService;
use core\service\user\UserService;

class EntraList extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $limit = isset($param['limit']) ? (int) $param['limit'] : 10;
        $status = isset($param['status']) ? (int) $param['status'] : ReserveEntraEnum::STATUS_VALID;
        $offset = isset($param['offset']) ? (int) $param['offset'] : 0;
        $userId = isset($param['userId']) ? (int) $param['userId'] : 0;
        $userInfo = !empty($userId) ? UserService::getUserById($userId) : [];

        $reservationEntraService = new ReservationEntraService();
        $result = $reservationEntraService->getReserveEntraDetailList($status, $limit, $offset, $userInfo);
        $this->json_data = $result;

    }
}
