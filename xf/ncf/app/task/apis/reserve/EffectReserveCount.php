<?php
/**
 * 有效预约次数
 */

namespace task\apis\reserve;

use task\lib\ApiAction;
use core\service\reserve\UserReservationService;

class EffectReserveCount extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $userId = isset($param['userId']) ? (int) $param['userId'] : 0;
        if (empty($userId)) {
            return false;
        }
        $userReservationService = new UserReservationService();
        $count = $userReservationService->getEffectReserveCountByUserId($userId);
        $this->json_data = $count;

    }
}
