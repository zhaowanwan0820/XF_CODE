<?php

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\deal\DealLoanRepayService;

class Loan extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];
        $startTime = $param['startTime'];
        $endTime = $param['endTime'];
        $limit = explode(',', $param['limit']);
        $type = $param['type'];
        $moneyType = $param['moneyType'];
        $repayStatus = $param['repayStatus'];
        $dealType = $param['dealType'];

        $dealLoanRepayService = new DealLoanRepayService();
        if (!empty($param['history']) && $param['history'] == 1){
            $dealLoanRepayService->is_history_db = true;
        }

        $result = $dealLoanRepayService->getRepayList($userId, $startTime, $endTime, $limit, $type, $moneyType, $repayStatus, $dealType);
        $dealLoanRepayService->is_history_db = false;
        $this->json_data = $result;
    }

}
