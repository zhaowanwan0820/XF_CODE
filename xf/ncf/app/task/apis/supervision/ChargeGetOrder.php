<?php
/**
 * 获取普惠存管充值订单
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionChargeModel;

class ChargeGetOrder extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $outOrderId = !empty($param['outOrderId']) ? (int)$param['outOrderId'] : 0;
            if (empty($outOrderId)) {
                throw new WXException('ERR_PARAM');
            }
            $result = SupervisionChargeModel::instance()->getChargeRecordByOutId($outOrderId);
            $this->json_data = is_object($result) ?  $result->getRow() : [];
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}
