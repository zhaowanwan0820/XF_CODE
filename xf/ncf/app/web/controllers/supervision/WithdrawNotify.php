<?php
/**
 * firstp2p网站- 免密提现至银行卡 回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;

class WithdrawNotify extends NotifyAction
{
    //使用订单锁
    protected $orderLock = true;

    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['amount'] = isset($requestData['amount']) ? intval($requestData['amount']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';
        $params['failReason'] = isset($requestData['failReason']) ? addslashes($requestData['failReason']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? intval($requestData['orderId']) : '';

        // 逻辑处理
        $supervisionObj = new SupervisionFinanceService();
        $result = $supervisionObj->finalWithdrawNotify($params);
        return $result;
    }
}
