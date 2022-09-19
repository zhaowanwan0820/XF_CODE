<?php
/**
 * 获取普惠充值限额
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\enum\SupervisionEnum;
use core\service\payment\PaymentUserAccountService;

class GetPhChargeLimit extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            // 银行ID
            $bankId = !empty($param['bankId']) ? intval($param['bankId']) : 0;
            if (empty($bankId)) {
                throw new WXException('ERR_PARAM');
            }
            // 用户ID
            $userId = !empty($param['userId']) ? intval($param['userId']) : 0;
            // 充值渠道
            $payChannel = !empty(SupervisionEnum::$chargeChannelConfig[$param['payChannel']]) ? $param['payChannel'] : SupervisionEnum::CHARGE_QUICK_CHANNEL;

            $obj = new PaymentUserAccountService();
            $this->json_data = $obj->getChargeLimitSubscription($bankId, $userId, $payChannel);
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}