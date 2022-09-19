<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\dao\UserModel;

class DealPrepayMsgEvent extends BaseEvent {
    private $_user_id;
    private $_params;
    private $_content;

    public function __construct($user_id, $params, $content) {
        $this->_user_id = $user_id;
        $this->_params = $params;
        $this->_content = $content;
    }

    public function execute() {
        send_user_msg("", $this->_content, 0, $this->_user_id, get_gmtime(), 0, 1, 9);
        $user = UserModel::instance()->find($this->_user_id);

        /*author:liuzhenpeng, modify:系统触发短信签名, date:2015-10-28*/
        $user['site_id'] = empty($user['site_id']) ? 1 : $user['site_id'];

        // SMSSend 提前还款回款通知
        \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_SMS_LOAN_REPAY', $this->_params, $user['id'], $user['site_id']);
        return true;
    }

    public function alertMails() {
        return array('wangyiming@ucfgroup.com', 'quanhengzhuang@ucfgroup.com', 'liangqiang@ucfgroup.com', 'wangjiantong@ucfgroup.com');
    }
}
