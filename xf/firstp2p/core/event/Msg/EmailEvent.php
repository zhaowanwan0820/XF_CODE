<?php
namespace core\event\Msg;

use NCFGroup\Task\Events\AsyncEvent;
use libs\db\MysqlDb;
use core\event\BaseEvent;
use core\service\DealMsgListService;

/**
 * EmailEvent
 * 异步发送邮件
 *
 * @uses AsyncEvent
 * @package default
 */
class EmailEvent extends BaseEvent
{
    private $_data = array();

    const SUCC_SMS = 1; // 处理成功
    const ERR_QUEUE_INSERT = 0; // 没有成功插入队列（redis故障）
    const ERR_DAEMON = 2; // 守护进程死亡或僵尸
    const ERR_SMS_API = 3; // 调用短信接口失败

    public function __construct($data) {
        $this->_data = $data;
    }

    public function execute() {
        $msg = $this->_data;
        if (empty($msg['address']) || empty($msg['content']) || empty($msg['title'])) {
            // 参数空也返回true
            return true;
        } else {
            $DealMsg = new DealMsgListService();
            $rs = $DealMsg->sendMsg($msg);
           /* if ($rs['is_send'] === false){
                return false;
            }*/
            unset($DealMsg);
        }
        return true;
    }

    public function alertMails(){

        return array('liangqiang@ucfgroup.com','zhaoxiaoan@ucfgroup.com');
    }

}

