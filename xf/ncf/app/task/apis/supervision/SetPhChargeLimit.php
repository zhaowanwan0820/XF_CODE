<?php
/**
 * 更新普惠充值限额
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;
use core\enum\SupervisionEnum;
use core\dao\supervision\BankLimitModel;

class SetPhChargeLimit extends ApiAction
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
            // 充值渠道
            $payChannel = !empty(SupervisionEnum::$chargeChannelConfig[$param['payChannel']]) ? $param['payChannel'] : SupervisionEnum::CHARGE_QUICK_CHANNEL;

            // 银行简码
            $bankCode = !empty($param['bankCode']) ? addslashes($param['bankCode']) : '';
            if (empty($bankCode)) {
                throw new WXException('ERR_PARAM');
            }
            // 充值类型
            $type = !empty($param['type']) ? addslashes($param['type']) : '';
            if (empty($type)) {
                throw new WXException('ERR_PARAM');
            }
            // 限额数据
            $limitInfo = !empty($param['limitInfo']) ? $param['limitInfo'] : [];
            if (empty($limitInfo)) {
                throw new WXException('ERR_PARAM');
            }

            // 获取指定银行的限额数据
            $limit = BankLimitModel::instance()->getLimitByChannelCode($payChannel, $bankCode);
            if (empty($limit) && empty($limitInfo['id'])) {
                // 新增限额数据
                $limitRet = BankLimitModel::instance()->createLimit($payChannel, $bankCode, $type, $limitInfo);
            } else {
                // 更新限额数据
                $limitRet = BankLimitModel::instance()->updateLimitById($limitInfo['id'], $limitInfo);
            }
            $this->json_data = $limitRet;
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}