<?php
/**
 * 获取普惠存管划转订单
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionTransferModel;

class WithdrawGetOrder extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $outOrderId = !empty($param['outOrderId']) ? (int)$param['outOrderId'] : 0;
            if (empty($outOrderId)) {
                throw new WXException('ERR_PARAM');
            }

            $this->json_data = SupervisionTransferModel::instance()->getTransferRecordByOutId($outOrderId);
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}