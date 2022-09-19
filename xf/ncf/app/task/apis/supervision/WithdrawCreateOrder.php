<?php
/**
 * 在普惠创建存管提现订单
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionWithdrawModel;

class WithdrawCreateOrder extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $accountId = !empty($param['accountId']) ? (int)$param['accountId'] : 0;
            $amount = !empty($param['amount']) ? (int)$param['amount'] : 0; // 提现金额,单位分
            $outOrderId = !empty($param['outOrderId']) ? (int)$param['outOrderId'] : 0;
            $bidId = !empty($param['bidId']) ? (int)$param['bidId'] : 0;
            $type = !empty($param['type']) ? (int)$param['type'] : 0;
            $limitId = !empty($param['limitId']) ? (int)$param['limitId'] : 0;
            if (empty($accountId) || empty($amount) || empty($outOrderId)) {
                throw new WXException('ERR_PARAM');
            }

            $ret = SupervisionWithdrawModel::instance()->createOrder($accountId, $amount, $outOrderId, $bidId, $type, $limitId);
            if ( ! $ret) {
                throw new WXException('ERR_CARRY_ORDER_CREATE');
            }
            $this->json_data = $ret;
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}