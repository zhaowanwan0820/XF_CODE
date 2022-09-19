<?php
namespace core\event;

use core\event\BaseEvent;
use libs\db\MysqlDb;
use libs\utils\Logger;
use NCFGroup\Task\Events\AsyncEvent;
use core\dao\MsgBoxModel;
use core\service\MsgBoxService;
use NCFGroup\Protos\Ptp\RequestMsgBoxSend;

/**
 * UserMsgEvent
 * 添加用户站内信息处理类
 *
 * @uses AsyncEvent
 * @package default
 */
class UserMsgEvent extends BaseEvent
{
    private $_title;

    private $_content;

    private $_fromUserId;

    private $_toUserId;

    private $_toBatchUserIds = array();

    private $_createTime;

    private $_sysMsgId = 0;

    private $_onlySend = false;

    private $_isNotice = false;

    private $_favId = 0;

    public function __construct(
        $title,
        $content,
        $fromUserId,
        $toUserId,
        $createTime,
        $sysMsgId = 0,
        $onlySend = false,
        $isNotice = false,
        $favId = 0
    ) {
        $this->_title = $title;
        $this->_content = $content;
        $this->_fromUserId = $fromUserId;
        $this->_createTime = $createTime;
        $this->_sysMsgId = $sysMsgId;
        $this->_onlySend = $onlySend;
        $this->_isNotice = $isNotice;
        $this->_favId = $favId;
        if (is_array($toUserId)) {
            $this->_toBatchUserIds = $toUserId;
        } else {
            $this->_toUserId = intval($toUserId);
        }
    }

    public function execute()
    {
        try {
            $request = new RequestMsgBoxSend();
            $request->setUserId(intval($this->_toUserId));
            $request->setBatchUserIds($this->_toBatchUserIds);
            $request->setType(intval($this->_isNotice));
            $request->setTitle(strval($this->_title));
            $request->setContent(strval($this->_content));
            $response = $GLOBALS['rpc']->callByObject(array(
                'service' => '\NCFGroup\Ptp\services\PtpMsgBox',
                'method' => 'msgBoxSend',
                'args' => $request
            ));

            if ($response->result !== true) {
                throw new \Exception('推送消息失败');
            }
            if (!empty($response->msg)) {
                Logger::info("SEND_BATCH_MSG_RES:".json_encode($response->msg));
            }
            \libs\utils\Monitor::add('MSG_BOX_SEND_SUCCESS');
        } catch (\Exception $e) {
            \libs\utils\Monitor::add('MSG_BOX_SEND_FAILED');
            Logger::info("MessagePushFailed. userId:{$this->_toUserId}, error:".$e->getMessage());
        }

        return true;
    }

    public function alertMails()
    {
        return array('quanhengzhuang@ucfgroup.com,luzhengshuai@ucfgroup.com');
    }
}
