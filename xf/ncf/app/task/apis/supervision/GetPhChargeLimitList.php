<?php
/**
 * 获取普惠充值限额列表
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use core\enum\SupervisionEnum;
use core\dao\supervision\BankLimitModel;

class GetPhChargeLimitList extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            // 充值渠道
            $payChannel = !empty($param['payChannel']) ? $param['payChannel'] : '';
            if (empty($payChannel)) {
                throw new WXException('ERR_PARAM');
            }
            if (is_string($payChannel) && empty(SupervisionEnum::$chargeChannelConfig[$payChannel])) {
                throw new WXException('ERR_PARAM');
            }
            // 银行简码
            $bankCode = !empty($param['bankCode']) ? addslashes($param['bankCode']) : '';

            // 查询该充值渠道的银行限额列表
            $list = BankLimitModel::instance()->getChargeLimitList($payChannel, $bankCode);
            if (!empty($list)) {
                foreach ($list as $key => $item) {
                    $list[$key]['day_quota'] = (int)$item['day_quota'] >= 0 ? bcdiv($item['day_quota'], 100, 0) : (int)$item['day_quota']; // 日最大限额,单位元(-1:无限额)
                    $list[$key]['max_quota'] = (int)$item['max_quota'] >= 0 ? bcdiv($item['max_quota'], 100, 0) : (int)$item['max_quota']; // 单笔最大限额,单位元(-1:无限额)
                    $list[$key]['month_quota'] = (int)$item['month_quota'] >= 0 ? bcdiv($item['month_quota'], 100, 0) : (int)$item['month_quota']; // 月最大限额,单位元(-1:无限额)
                    $list[$key]['limit_intro'] = !empty($item['limit_intro']) ? $item['limit_intro'] : '';
                    $list[$key]['limit_json'] = !empty($item['limit_json']) ? $item['limit_json'] : '';
                }
            }
            $this->json_data = $list;
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}