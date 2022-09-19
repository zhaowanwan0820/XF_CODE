<?php

namespace api\processor;

use core\service\user\UserService;

class UserSignup extends Processor {
    public function beforeInvoke() {
    }

    public function afterInvoke() {
        // 登陆成功之后，对于wap设置session
        $expire = 3600 * 24;
        $_SESSION['wapSessionExpire'] = $expire;
        setcookie('PHPSESSID', session_id(), time() + $expire, '/');

        $token = $this->fetchResult['token'];
        $tokenInfo = UserService::getUserByCode($token);
        if (!empty($tokenInfo['code'])) {
            $this->setApiRespErr($tokenInfo['code'], $tokenInfo['reason']);
            return false;
        }

        $userInfo = $tokenInfo['user'];
        $userInfo['token'] = $token;
        // 在session中存储登陆的用户
        UserService::setLoginUser($userInfo);

        unset($this->fetchResult['token']);
        $this->setApiRespData($this->fetchResult);
    }

}
