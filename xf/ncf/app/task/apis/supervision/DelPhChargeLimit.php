<?php
/**
 * 删除某条普惠充值限额
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\BankLimitModel;

class DelPhChargeLimit extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            // 限额自增ID
            $id = !empty($param['id']) ? intval($param['id']) : 0;
            if (empty($id)) {
                throw new WXException('ERR_PARAM');
            }

            $this->json_data = BankLimitModel::instance()->delLimit($id);
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}