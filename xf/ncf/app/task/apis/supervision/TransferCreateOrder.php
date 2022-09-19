<?php
/**
 * 在普惠创建存管划转订单
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionTransferModel;

class TransferCreateOrder extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $accountId = !empty($param['accountId']) ? (int)$param['accountId'] : 0;
            $amount = !empty($param['amount']) ? (int)$param['amount'] : 0; // 提现金额,单位分
            $outOrderId = !empty($param['outOrderId']) ? (int)$param['outOrderId'] : 0;
            $direction = !empty($param['direction']) ? (int)$param['direction'] : SupervisionTransferModel::DIRECTION_TO_SUPERVISION;
            $needChangeMoney = !empty($param['needChangeMoney']) ? (int)$param['needChangeMoney'] : 0;
            if (empty($accountId) || empty($amount) || empty($outOrderId)) {
                throw new WXException('ERR_PARAM');
            }

            $ret = SupervisionTransferModel::instance()->createOrder($accountId, $amount, $outOrderId, $direction, $needChangeMoney);
            if ( ! $ret) {
                throw new WXException('ERR_TRANSFER_FAILED');
            }
            $this->json_data = $ret;
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}