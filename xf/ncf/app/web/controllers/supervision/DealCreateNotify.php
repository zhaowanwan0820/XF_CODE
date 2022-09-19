<?php
/**
 * firstp2p网站-标的报备回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionDealService;

class DealCreateNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['bidId'] = isset($requestData['bidId']) ? addslashes($requestData['bidId']) : '';
        $params['bidStatus'] = isset($requestData['bidStatus']) ? addslashes($requestData['bidStatus']) : '';
        $params['bankAuditStatus'] = isset($requestData['bankAuditStatus']) ? addslashes($requestData['bankAuditStatus']) : '';

        // 逻辑处理
        $supervisionObj = new SupervisionDealService();
        $result = $supervisionObj->dealReportNotify($params);
        return $result;
    }
}
