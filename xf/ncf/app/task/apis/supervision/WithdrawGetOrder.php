<?php
/**
 * 获取普惠存管提现订单
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionWithdrawModel;

class WithdrawGetOrder extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $accountId = !empty($param['accountId']) ? (int)$param['accountId'] : 0;
            $bidId = !empty($param['bidId']) ? (int)$param['bidId'] : 0;
            $outOrderId = !empty($param['outOrderId']) ? (int)$param['outOrderId'] : 0;
            if (empty($accountId) && empty($bidId) && empty($outOrderId)) {
                throw new WXException('ERR_PARAM');
            }

            $orderInfo = [];
            if (!empty($accountId) && !empty($bidId)) {
                $orderInfo = SupervisionWithdrawModel::instance()->getWithdrawSuccessByUserIdBid($accountId, $bidId);
            }else if (!empty($outOrderId)) {
                $orderInfo = SupervisionWithdrawModel::instance()->getWithdrawRecordByOutId($outOrderId);
            }
            $this->json_data = $orderInfo;
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}