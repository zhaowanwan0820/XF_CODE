<?php
/**
 * 判断用户是否可以快速提现
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\service\supervision\SupervisionFinanceService;

class CanFastWithdraw extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $accountId = !empty($param['accountId']) ? addslashes($param['accountId']) : '';
            $amount = !empty($param['amount']) ? intval($param['amount']) : 0;
            if (empty($accountId)) {
                throw new WXException('ERR_PARAM');
            }

            $sfs = new SupervisionFinanceService();

            $this->json_data = $sfs->canFastWithdraw($accountId, $amount) ? 1 : 0;
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}
