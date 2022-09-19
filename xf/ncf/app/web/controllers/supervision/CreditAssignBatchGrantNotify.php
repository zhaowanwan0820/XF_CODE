<?php
/**
 * firstp2p网站-智多鑫-批量标的债权转让回调-异步回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\duotou\DtPaymenyService;

class CreditAssignBatchGrantNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['amount'] = isset($requestData['amount']) ? (int)$requestData['amount'] : 0;
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['failReason'] = isset($requestData['failReason']) ? addslashes($requestData['failReason']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $dtPaymenyService = new DtPaymenyService();
        return $dtPaymenyService->creditAssignBatchGrantNotify($params);
    }
}
