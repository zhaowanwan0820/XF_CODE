<?php
/**
 *-------------------------------------------------------
 * 第三方投资接口通知服务
 *-------------------------------------------------------
 * 2015-11-09 11:35:38
 *-------------------------------------------------------
 */

namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\PaymentApi;
use core\event\BaseEvent;

use core\service\InvestNotifyService;
/**
 * InvestNotifyEvent
 * 第三方投资接口通知服务
 *
 * @uses AsyncEvent
 * @package default
 */
class InvestNotifyEvent extends BaseEvent
{
    public $notifyData;
    public $taskId;

    public function __construct($notifyData) {
        $this->notifyData = $notifyData;
    }

    /**
     * 请求支付接口
     */
    public function execute() {
        $investNotifyService = new InvestNotifyService();
        try {
            $this->notifyData['event_id'] = $this->taskId;
            return $investNotifyService->notify($this->notifyData);
        }
        catch(\Exception $e) {
            PaymentApi::log('InvestNotifyEvent failed, request data:'.json_encode($this->notifyData, JSON_UNESCAPED_UNICODE));
            return false;
        }
        return true;
    }

    public function alertMails() {
        return array('wangqunqiang@ucfgroup.com');
    }
}
