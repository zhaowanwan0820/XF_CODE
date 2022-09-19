<?php
/**
 * firstp2p网站-智多鑫-预约冻结回调-异步回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\duotou\DtPaymenyService;

class BookfreezeCreateNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['userId'] = isset($requestData['userId']) ? (int)$requestData['userId'] : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? (int)$requestData['orderId'] : '';
        $params['freezeAccountAmount'] = isset($requestData['freezeAccountAmount']) ? (int)$requestData['freezeAccountAmount'] : '';
        $params['freezeType'] = isset($requestData['freezeType']) ? addslashes($requestData['freezeType']) : '';
        $params['failReason'] = isset($requestData['failReason']) ? addslashes($requestData['failReason']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $dtPaymenyService = new DtPaymenyService();
        return $dtPaymenyService->bookfreezeCreateNotify($params);
    }
}
