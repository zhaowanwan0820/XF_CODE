<?php
/**
 * firstp2p网站-流标回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionDealService;

class DealCancelNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['bidId'] = isset($requestData['bidId']) ? addslashes($requestData['bidId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';

        // 逻辑处理
        $supervisionObj = new SupervisionDealService();
        return $supervisionObj->dealCancelNotify($params);
    }
}
