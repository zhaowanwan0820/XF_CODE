<?php
/**
 * firstp2p网站 银行还款回调
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\creditloan;

use web\controllers\supervision\NotifyAction;
use core\service\speedloan\SpeedloanService;
use core\service\supervision\SupervisionFinanceService;

class WithdrawNotify extends NotifyAction
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
        $status = $params['status'] == 'S' ? '00' : '01';
        $result = SpeedloanService::payCallback($params['orderId'], $status, $params['remark']);
        $sbs = new SupervisionFinanceService;
        if (!$result) {
            return $sbs->responseFailure();
        }
        return $sbs->responseSuccess();
    }
}
