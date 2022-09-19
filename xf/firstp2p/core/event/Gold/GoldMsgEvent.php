<?php
/**
 *
 */
namespace core\event\Gold;

use libs\utils\Logger;
use core\event\BaseEvent;
use core\dao\UserModel;
use libs\sms\SmsServer;


\FP::import("libs.libs.msgcenter");


 abstract class GoldMsgEvent extends BaseEvent {

    protected $msgList = array();
    protected $msgcenter;

    public function __construct()
    {
        $this->msgcenter = new \Msgcenter();
    }

    public function execute() {
        if (app_conf('SMS_ON') == 1) {
            return $this->sendMsg();
        }
        return true;
    }

//设置短信内容
 abstract function setMsgList($msg);

    private function sendMsg(){
        if(empty($this->msgList)){
            return true;
        }
        foreach($this->msgList as $val){
            if(!empty($val['mobile'])){
                $mobile = $val['mobile'];
            }else{
                $user = UserModel::instance()->find($val['userId']);
                if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
                {
                    $mobile = 'enterprise';
                } else {
                    $mobile = $user['mobile'];
                }
                if(empty($mobile)){
                    continue;
                }
            }
            $content = $val['content'];
            $tplName = $val['tplName'];
            SmsServer::instance()->send($mobile, $tplName, $content, $user['id']);
        }
        return $this->msgcenter->save();
    }

    public function alertMails() {
        return array('zhaoxiaoan@ucfgroup.com','liangqiang@ucfgroup.com','wangzhen3@ucfgroup.com','gengkuan@ucfgroup.com');
    }
}
