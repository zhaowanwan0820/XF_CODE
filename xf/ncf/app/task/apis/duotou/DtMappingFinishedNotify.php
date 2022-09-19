<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtDepositoryService;

class DtMappingFinishedNotify extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $money = $param['money'];
        $orderId = intval($param['token']); //订单id
        $tableNum = intval($param['tableNum']); //分表数量
        $date = intval($param['date']); //匹配日期
        $ds = new DtDepositoryService();
        $this->json_data = $ds->dtMappingFinishCallBack($orderId, $tableNum, $date);
    }
}
