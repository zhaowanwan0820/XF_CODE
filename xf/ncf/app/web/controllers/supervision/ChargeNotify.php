<?php
/**
 * firstp2p网站充值回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;

class ChargeNotify extends NotifyAction
{
    //使用订单锁
    protected $orderLock = true;

    public function process($requestData)
    {
        //逻辑处理
        $supervisionObj = new SupervisionFinanceService();
        $result = $supervisionObj->chargeNotify($requestData);
        return $result;
    }

}
