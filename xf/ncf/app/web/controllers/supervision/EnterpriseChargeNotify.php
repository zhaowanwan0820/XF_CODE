<?php
/**
 * 企业用户充值回调接口
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;

class EnterpriseChargeNotify extends NotifyAction
{
    //使用订单锁
    protected $orderLock = true;

    public function process($requestData)
    {
        //逻辑处理
        $supervisionObj = new SupervisionFinanceService();
        return $supervisionObj->chargeNotify($requestData);
    }
}
