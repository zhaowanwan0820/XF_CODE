<?php
/**
 * 账户信息
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\dealload\DealLoadService;

class GetTotalLoanMoney extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];
        $startTime = $param['startTime'];
        $endTime = $param['endTime'];
        $dealStatus = explode(',', $param['dealStatus']);

        $data = (new DealLoadService())->getTotalLoanMoneyByUserId($userId, $startTime, $endTime, $dealStatus);
        $this->json_data = $data;
    }
}