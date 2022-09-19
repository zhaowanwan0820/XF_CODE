<?php
/**
 * firstp2p网站- 提现至超级账户 回调接口
 *
 */
namespace web\controllers\supervision;

use web\controllers\supervision\NotifyAction;
use core\service\supervision\SupervisionFinanceService;
use core\dao\supervision\SupervisionTransferModel;

class SuperWithdrawNotify extends NotifyAction
{
    public function process($requestData)
    {
        // 逻辑处理
        $supervisionObj = new SupervisionFinanceService();
        return $supervisionObj->superRechargeNotify($requestData['orderId'], SupervisionTransferModel::DIRECTION_TO_WX);
    }
}
