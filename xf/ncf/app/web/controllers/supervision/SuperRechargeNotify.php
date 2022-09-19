<?php
/**
 * 从超级账户充值到网贷账户回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;
use core\dao\supervision\SupervisionTransferModel;

class SuperRechargeNotify extends NotifyAction
{
    public function process($requestData)
    {
        $supervisionObj = new SupervisionFinanceService();
        // 订单创建60s内不接受异步通知
        $record = SupervisionTransferModel::instance()->getTransferRecordByOutId($requestData['orderId']);
        if (isset($record['create_time']) && time() - $record['create_time'] < 60) {
            echo $supervisionObj->responseFailure('ERR_REQUEST_FREQUENCY_TOO_FAST');
            return;
        }

        //逻辑处理
        return $supervisionObj->superRechargeNotify($requestData['orderId']);
    }

}
