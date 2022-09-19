<?php
/**
 * 从超级账户充值到网贷账户回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;

class superRechargeSecretNotify extends NotifyAction
{
    public function process($requestData)
    {
        //逻辑处理
        $supervisionObj = new SupervisionFinanceService();
        return $supervisionObj->superRechargeNotify($requestData['orderId']);
    }

}
