<?php
/**
 * PushEvent.php.
 * User: luzhengshuai
 * Date: 20/04/15
 * Time: 14:22
 */
namespace NCFGroup\Ptp\Instrument\Event;

use NCFGroup\Task\Events\AsyncEvent;

use NCFGroup\Common\Library\Api;
use NCFGroup\Common\Library\Logger;
use NCFGroup\Ptp\services\PtpPushService;
use NCFGroup\Ptp\services\PtpPushChannelService;

class PushEvent implements AsyncEvent
{

    public $appId;
    public $userId;
    public $channelId;
    public $osType;
    public $content;
    public $badge;
    public $params = array();

    public function __construct($appId, $userId, $channelId, $osType, $content, $badge, $params)
    {
        $this->appId = $appId;
        $this->userId = $userId;
        $this->channelId = $channelId;
        $this->osType = $osType;
        $this->content = $content;
        $this->badge = $badge;
        $this->params = $params;
    }

    public function execute()
    {
        $paramsJson = json_encode($this, JSON_UNESCAPED_UNICODE);
        \libs\utils\Logger::info("PushEventStart. channelId:{$this->channelId}");

        try {
            $channelService = new PtpPushChannelService();
            $result = $channelService->toSingle($this->appId, $this->osType, $this->channelId, $this->content, $this->badge, $this->params);

            if ($result === false) {
                \libs\utils\Monitor::add('PUSH_EVENT_FAILED');
                \libs\utils\Logger::info("PushEventFailed. params:{$paramsJson}, error:".json_encode($channelService->error));
                return true;
            }

            \libs\utils\Logger::info("PushEventSuccess. params:{$paramsJson}, result:".json_encode($result));
            \libs\utils\Monitor::add('PUSH_EVENT_SUCCESS');
            return true;
        } catch (\Exception $e) {
            \libs\utils\Logger::info("PushEventException. params:{$paramsJson}, error:".$e->getMessage());
            \libs\utils\Monitor::add('PUSH_EVENT_EXCEPTION');
            //不再重试
            return true;
        }
    }

    public function alertMails()
    {
        return array('luzhengshuai@ucfgroup.com', 'quanhengzhuang@ucfgroup.com');
    }

}
