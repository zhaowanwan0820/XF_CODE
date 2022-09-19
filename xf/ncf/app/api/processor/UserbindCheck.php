<?php

namespace api\processor;

use Backend\Api\Plugins\Utils;

class UserbindCheck extends Processor {

    public function beforeInvoke() {
        if(!empty($this->params['client_token']) && $this->params['client_token'] == $_SESSION['pass_client_token']){
            $userInfo = Utils::getLoginUser();
            if(!empty($userInfo)){
                $this->setApiRespData(array("status" => 1));
                return false;
            }
        }

        $this->params['client_bind_sign'] = $_COOKIE['bind_sign'];
    }

    public function afterInvoke() {
        $result = $this->fetchResult;

        $ret = array();
        if($result['status'] == 1)
        {
            $_SESSION['pass_client_token'] = $this->params['client_token'];
            Utils::setLoginUser($result['userInfo']);
            setcookie('bind_sign', $result['bindSign'],time()+30*24*3600,"/",Utils::getHttpHost(),false,true);

            $ret = array("status" => 1);
        }
        else
        {
            $_SESSION['user_bind'] = true;
            $_SESSION['bind_data'] = $result['bindData'];

            $ret = array(
                "status" => 2,
                "mobile" => $result['bindData']['checkMobile'],
                "applogo" => $result['bindData']['openBindData']['appInfo']['appLogo'],
                "app_name" => $result['bindData']['openBindData']['appInfo']['appName'],
            );
        }

        $this->setApiRespData($ret);
    }

}