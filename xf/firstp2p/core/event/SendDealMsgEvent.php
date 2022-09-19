<?php
namespace core\event;

use core\event\BaseEvent;
use core\service\DealService;

class SendDealMsgEvent extends BaseEvent
{
    private $_deal_id;
    private $_type;

    public function __construct($deal_id = 0, $type = 'full') {
        $this->_deal_id = $deal_id;
        $this->_type = $type;
    }

    public function execute()
    {
        $deal_service = new DealService();
        $rs = $deal_service->sendDealMessage($this->_deal_id, $this->_type);
        return $rs;
    }

    public function alertMails()
    {
        // 这样硬编码 太粗暴了吧?
        return array('zhanglei5@ucfgroup.com');
    }
}
