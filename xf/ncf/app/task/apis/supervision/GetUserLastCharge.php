<?php
/**
 * 获取指定用户最后一次普惠存管充值订单
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionChargeModel;

class GetUserLastCharge extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $userId= !empty($param['userId']) ? (int)$param['userId'] : 0;
            if (empty($userId)) {
                throw new WXException('ERR_PARAM');
            }
            $result = SupervisionChargeModel::instance()->getUserLastCharge($userId);
            $this->json_data = is_object($result) ? $result->getRow() : [];
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}
