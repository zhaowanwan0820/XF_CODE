<?php

namespace api\processor;

use core\service\user\UserService;

class UserbindDone extends Processor {

    public function beforeInvoke() {
        if (empty($_SESSION['user_bind'])){
            $this->setApiRespErr("ERR_TOKEN_ERROR", "会话过期");
            return false;
        }

        $this->params['client_bind_sign'] = $_COOKIE['bind_sign'];
        $this->params['bind_data'] = json_encode($_SESSION['bind_data']);
        $euid = '';
        if(!empty($_REQUEST['euid'])){
            $euid = $_REQUEST['euid'];
        }elseif(!empty($_COOKIE['euid'])){
            $euid = $_COOKIE['euid'];
        }
        $this->params['euid'] = $euid;
    }

    public function afterInvoke() {
        $result = $this->fetchResult;

        setcookie('bind_sign', $result['bindSign'], time()+30*24*3600,"/", $this->getHttpHost(), false, true);
        if (!empty($result['link_coupon'])) {
            setcookie('link_coupon', $result['link_coupon'],0,"/",Utils::getHttpHost(),false,true);
        }

        $_SESSION['pass_client_token'] = $_SESSION['bind_data']['openBindData']['userParam']['params']['client_token'];
        $_SESSION['user_bind'] = false;
        
        UserService::setLoginUser($result['userInfo']);

        $this->setApiRespData(array());
    }

}