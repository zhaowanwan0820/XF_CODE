<?php
/**
 * firstp2p网站-批量转账回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;
use core\service\supervision\NongdanService;

class BatchTransferNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['amount'] = isset($requestData['amount']) ? intval($requestData['amount']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $result = [
            'respCode'  => '00',
            'status'    => 'S',
            'respMsg'   => '',
        ];
        if ('T' == $params['orderId']{0} && 'O' == $params['orderId']{1})
        {
            $nongdanService = new NongdanService();
            $processResult = $nongdanService->processBatchTransfer($params);
            if ($processResult != true)
            {
                $result['respCode'] = '01';
                $result['status']   = 'F';
            }
        } else {
            $supervisionObj = new SupervisionFinanceService();
            $result = $supervisionObj->batchTransferNotify($params);
        }
        return $result;
    }
}
