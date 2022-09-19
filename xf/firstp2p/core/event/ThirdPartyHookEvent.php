<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\service\ThirdPartyHookService;


class ThirdPartyHookEvent extends BaseEvent
{
    // 第三方回调通知callback地址
    private $_url;
    // 第三方标示，用于日志统计
    private $_channel = 'wf';
    // 发送方式
    private $_method;

    public function __construct($url, $params, $channel) {
        if(isset($channel) && !empty($channel))
            $this->_channel = $channel;
        $this->_url = $url;
        $this->_params = $params;
    }

    public function execute() {
        $success = -998;
        $tphs = $this->getChannelService($this->_channel);
        $success = $tphs->syncCall($this->_url, $this->_params, $this->_channel);
        if($success < 0 || $tphs === false){
            $outputParams = json_encode($this->_params);
            $tphs->writeLog(sprintf("通知失败，errorNo:{$success} | notify_url:%s | params:%s",$this->_url,$outputParams),$this->_channel);
            throw new \Exception(sprintf("通知失败，errorNo:{$success} | notify_url:%s | params:%s | channel:%s",$this->_url,$outputParams,$this->_channel));
        }
        return true;
    }

    protected function getChannelService($channel){
        $className = $channel;
        $class = "\\core\\service\\curlHook\\".$className."HookService";
        $channelService = new $class();
        return $channelService;
    }

    public function alertMails() {
        return array('wangfei5@ucfgroup.com','zhangzhuyan@ucfgroup.com');
    }
}
