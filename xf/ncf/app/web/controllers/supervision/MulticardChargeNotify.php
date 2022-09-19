<?php
/**
 * firstp2p网站多银行卡充值回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;
use libs\utils\Logger;

class MulticardChargeNotify extends NotifyAction
{

    //使用订单锁
    protected $orderLock = true;

    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['amount'] = isset($requestData['amount']) ? addslashes($requestData['amount']) : '';
        $params['failReason'] = isset($requestData['failReason']) ? addslashes($requestData['failReason']) : '';
        $params['errMsg'] = isset($requestData['errMsg']) ? addslashes($requestData['errMsg']) : '';

        // 逻辑处理
        $supervisionObj = new SupervisionFinanceService();
        return $supervisionObj->chargeNotify($params);
    }
}