<?php
/**
 * 随鑫约卡片列表
 * 废弃
 */

namespace task\apis\reserve;

use task\lib\ApiAction;
use core\enum\ReserveCardEnum;
use core\enum\DealEnum;
use core\service\reserve\ReservationCardService;
use core\service\user\UserService;

class CardList extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $limit = isset($param['limit']) ? (int) $param['limit'] : 10;
        $status = isset($param['status']) ? (int) $param['status'] : ReserveCardEnum::STATUS_VALID;
        $offset = isset($param['offset']) ? (int) $param['offset'] : 0;
        $userId = isset($param['userId']) ? (int) $param['userId'] : 0;
        $userInfo = !empty($userId) ? UserService::getUserById($userId) : [];

        $reservationCardService = new ReservationCardService();
        $result = $reservationCardService->getReserveCardList($limit, $status, $offset, [DealEnum::DEAL_TYPE_GENERAL], $userInfo);
        $this->json_data = $result;

    }
}
