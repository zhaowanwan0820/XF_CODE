<?php
/**
 * 代偿回调
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionDealService;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class ReplaceRepayNotify extends NotifyAction
{
    public function process($requestData)
    {
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? trim($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        $params['status'] = isset($requestData['status']) ? trim($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? intval($requestData['remark']) : 0;

        //逻辑处理
        $supervisionObj = new SupervisionDealService();
        return $supervisionObj->dealRepayNotify($params);
    }
}
