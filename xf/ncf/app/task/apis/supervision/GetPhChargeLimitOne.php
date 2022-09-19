<?php
/**
 * 获取某条普惠充值限额
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\dao\supervision\BankLimitModel;

class GetPhChargeLimitOne extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            // 充值渠道
            $payChannel = !empty($param['payChannel']) ? addslashes($param['payChannel']) : '';
            if (empty($payChannel)) {
                throw new WXException('ERR_PARAM');
            }
            // 银行简码
            $code = !empty($param['code']) ? addslashes($param['code']) : '';
            if (empty($code)) {
                throw new WXException('ERR_PARAM');
            }

            $limitInfo = BankLimitModel::instance()->getLimitByChannelCode($payChannel, $code);
            $this->json_data = (is_object($limitInfo) && !empty($limitInfo)) ? $limitInfo->getRow() : [];
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}