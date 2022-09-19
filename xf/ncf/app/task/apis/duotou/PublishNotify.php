<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\user\UserService;
use libs\sms\SmsServer;

class PublishNotify extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $userId = intval($param['userId']); //用户Id

        //发送短信通知
        if (1 == app_conf('SMS_ON')) {
            $user = UserService::getUserById($userId);
            $this->json_data = SmsServer::instance()->send($user['mobile'], 'TPL_SMS_DTB_PUBLISH', array(), $user['id']);
        }
    }
}
