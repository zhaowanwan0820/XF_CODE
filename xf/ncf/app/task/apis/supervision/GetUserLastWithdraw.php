<?php
/**
 * 获取指定用户最后一次普惠存管提现成功订单
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionWithdrawModel;

class GetUserLastWithdraw extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $userId= !empty($param['userId']) ? (int)$param['userId'] : 0;
            if (empty($userId)) {
                throw new WXException('ERR_PARAM');
            }
            $this->json_data = SupervisionWithdrawModel::instance()->getUserLastWithdraw($userId);
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}
