<?php
/**
 * 获取账户普惠存管充值记录
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\SupervisionChargeModel;

class ChargeGetLogs extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $accountId = !empty($param['accountId']) ? (int)$param['accountId'] : 0;
            $ctime = !empty($param['ctime']) ? (int)$param['ctime'] : 0;
            $count = !empty($param['count']) ? (int)$param['count'] : 0;
            $offset = !empty($param['offset']) ? (int)$param['offset'] : 0;
            if (empty($accountId)) {
                throw new WXException('ERR_PARAM');
            }

            $this->json_data = SupervisionChargeModel::instance()->getChargeLogs($accountId, $ctime, $count, $offset);
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}