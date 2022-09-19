<?php
/**
 *-------------------------------------------------------
 * 第三方投资消费同步支付
 *-------------------------------------------------------
 * 2015-11-09 11:35:38
 *-------------------------------------------------------
 */

namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\PaymentApi;
use core\event\BaseEvent;

use core\service\PaymentService;
use core\dao\TradeLogModel;
/**
 *  TradeLogSyncEvent
 * 第三方投资消费同步支付
 *
 * @uses AsyncEvent
 * @package default
 */
class TradeLogSyncEvent extends BaseEvent
{
    public $taskId;

    public function __construct($tradeId) {
        $this->tradeId= $tradeId;
    }

    /**
     * 请求支付接口
     */
    public function execute() {
        $requestParams = array();
        $paymentService = new PaymentService();
        try {
            $trade = TradeLogModel::instance()->find($this->tradeId);
            $requestParams = array(
                'userId' => $trade['payerId'],
                'outMerchantId' => $trade['merchantNo'],
                'remark' => '',
                'amount' => $trade['amount'],
                'outOrderId' => $trade['outOrderId'],
                'projectId' => '1',
                'curType' => 'CNY',
            );
            return $paymentService->investSync($requestParams);
        }
        catch(\Exception $e) {
            PaymentApi::log('TradeLogSyncEvent failed, request data:'.json_encode($requestParams, JSON_UNESCAPED_UNICODE));
            return false;
        }
        return true;
    }

    public function alertMails() {
        return array('wangqunqiang@ucfgroup.com');
    }
}
