<?php
/**
 * firstp2p网站-投资验密回调接口只记录无密投资接口的通知
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionDealService;

class InvestCreateNotifyLogOnly extends NotifyAction
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
        ///$result = $this->rpc->local('SupervisionDealService\investCreateNotify', [$params]);
        $supervisionObj = new SupervisionDealService();
        $result = ['respCode' => '00', 'respMsg' => ''];
        return $result;
    }
}
