<?php
/**
 * firstp2p网站-标的还款回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionDealService;

class DealRepayNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $supervisionObj = new SupervisionDealService();
        return $supervisionObj->dealRepayNotify($params);
    }
}
