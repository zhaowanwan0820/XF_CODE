<?php
namespace core\event;
use core\event\BaseEvent;
use core\service\MsgBoxService;
use libs\utils\Logger;

/**
 * 预约投资下发合同消息
 */
class ReserveDealContractMsgEvent extends BaseEvent
{
    private $userId;
    private $startTime;
    private $endTime;

    public function __construct($userId, $startTime, $endTime) {
        $this->userId = $userId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

   public function execute ()
   {
        Logger::info('ReserveDealContractMsgEvent');
        $content = sprintf('您于%s通过网信随心约预约匹配的项目合同已下发。', date('Y年m月d日', $this->startTime));
        $msgbox = new MsgBoxService();
        $msgbox->create($this->userId, 32, '合同下发', $content);
        //send_user_msg("合同下发",$content,0,$this->userId,get_gmtime(),0,true,32);
        return true;
   }

    public function alertMails ()
    {
        return array(
            'weiwei12@ucfgroup.com'
        );
    }
}

