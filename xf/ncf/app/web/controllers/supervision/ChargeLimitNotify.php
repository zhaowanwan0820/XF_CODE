<?php
/**
 * 普惠银行卡限额订阅回调通知接口
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;

class ChargeLimitNotify extends NotifyAction
{
    public function process($requestData)
    {
        //逻辑处理
        $supervisionObj = new SupervisionFinanceService();
        return $supervisionObj->bankLimitSubscriptionNotify($requestData);
    }
}