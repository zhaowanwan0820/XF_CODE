<?php
/**
 * firstp2p网站-智多鑫-取消预约冻结回调-异步回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\duotou\DtPaymenyService;

class BookfreezeCancelNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['userId'] = isset($requestData['userId']) ? (int)$requestData['userId'] : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['amount'] = isset($requestData['amount']) ? (int)$requestData['amount'] : '';
        $params['feeUserId'] = isset($requestData['feeUserId']) ? (int)$requestData['feeUserId'] : '';
        $params['feeAmount'] = isset($requestData['feeAmount']) ? (int)$requestData['feeAmount'] : '';
        $params['unFreezeType'] = isset($requestData['unFreezeType']) ? addslashes($requestData['unFreezeType']) : '';
        $params['failReason'] = isset($requestData['failReason']) ? addslashes($requestData['failReason']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $dtPaymenyService = new DtPaymenyService();
        return $dtPaymenyService->bookfreezeCancelNotify($params);
    }
}
