<?php
/**
 * firstp2p网站-智多鑫-批量债权转让投资回调-异步回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\duotou\DtPaymenyService;

class BookCreditBatchNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['resultList'] = isset($requestData['resultList']) ? $requestData['resultList'] : [];
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $dtPaymenyService = new DtPaymenyService();
        return $dtPaymenyService->bookCreditBatchNotify($params);
    }
}
