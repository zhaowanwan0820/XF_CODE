<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtDepositoryService;
use libs\utils\Logger;

class DtRepayCallBack extends ApiAction
{
    public function invoke()
    {
        try {
	        $param = $this->getParam();
	        $orderId = intval($param['p2pDealRepayId']); //订单id
	        $manageId = intval($param['manageId']); // 管理机构ID
	        $ds = new DtDepositoryService();
	        $this->json_data = $ds->dtRepayCallBack($orderId, $manageId);
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}
