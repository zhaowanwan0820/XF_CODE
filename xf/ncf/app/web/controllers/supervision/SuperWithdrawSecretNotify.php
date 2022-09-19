<?php
/**
 * firstp2p网站- 验密提现至超级账户 回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;

class SuperWithdrawSecretNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['amount'] = isset($requestData['amount']) ? addslashes($requestData['amount']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $supervisionObj = new SupervisionFinanceService();
        return $supervisionObj->superWithdrawNotify($params);
    }
}
