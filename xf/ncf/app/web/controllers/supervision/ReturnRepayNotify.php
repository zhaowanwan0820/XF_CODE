<?php
/**
 * 还代偿款回调
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionService;
use core\service\supervision\NongdanService;

class ReturnRepayNotify extends NotifyAction
{
    public function process($requestData)
    {
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? trim($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        $params['status'] = isset($requestData['status']) ? trim($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? intval($requestData['remark']) : 0;

        // 逻辑处理
        $result = [
            'respCode'  => '00',
            'status'    => 'S',
            'respMsg'   => '',
        ];
        $nongdanService = new NongdanService();
        $processResult = $nongdanService->processReturnRepay($params);
        if ($processResult != true)
        {
            $result['respCode'] = '01';
            $result['status']   = 'F';
        }
        $supervisionObj = new SupervisionService();
        echo $supervisionObj->getApi()->response($result);

    }
}
