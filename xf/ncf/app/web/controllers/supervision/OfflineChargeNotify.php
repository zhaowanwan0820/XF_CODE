<?php
/**
 * 网贷p2p大额充值回调接口
 * @author guofeng3<guofeng3@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;

class OfflineChargeNotify extends NotifyAction
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
