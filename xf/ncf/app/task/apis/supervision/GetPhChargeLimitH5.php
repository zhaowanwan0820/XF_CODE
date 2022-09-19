<?php
/**
 * 获取普惠充值限额-m站
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\enum\SupervisionEnum;
use core\service\payment\PaymentUserAccountService;

class GetPhChargeLimitH5 extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            // 用户ID
            $userId = !empty($param['userId']) ? intval($param['userId']) : 0;
            if (empty($userId)) {
                throw new WXException('ERR_PARAM');
            }
            // 充值渠道
            $payChannel = !empty(SupervisionEnum::$chargeChannelConfig[$param['payChannel']]) ? $param['payChannel'] : SupervisionEnum::CHARGE_QUICK_CHANNEL;

            $obj = new PaymentUserAccountService();
            $this->json_data = $obj->getChargeLimitSubscriptionH5($userId, $payChannel);
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}