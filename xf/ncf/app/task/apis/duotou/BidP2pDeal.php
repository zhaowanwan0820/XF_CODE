<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtDepositoryService;

class BidP2pDeal extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $money = $param['money'];
        $orderId = $param['orderId'];
        $p2pDealId = $param['p2pDealId'];
        $userId = $param['userId'];
        $transParams = $param['transParams'];
        $service = new DtDepositoryService();
        $this->json_data = $service->sendDtBidRequest($orderId, $userId, $p2pDealId, $money, $transParams);
    }
}
