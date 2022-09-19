<?php
/**
 * 在普惠创建存管充值订单
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionChargeModel;

class ChargeCreateOrder extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $accountId = !empty($param['accountId']) ? (int)$param['accountId'] : 0;
            $amount = !empty($param['amount']) ? (int)$param['amount'] : 0; // 充值金额,单位分
            $outOrderId = !empty($param['outOrderId']) ? (int)$param['outOrderId'] : 0;
            $platform = !empty($param['platform']) ? (int)$param['platform'] : 0;
            if (empty($accountId) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }

            $ret = SupervisionChargeModel::instance()->createOrder($accountId, $amount, $outOrderId, $platform);
            if ( ! $ret) {
                throw new WXException('ERR_CREATE_CHARGE_FAILED');
            }
            $this->json_data = $ret;
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}