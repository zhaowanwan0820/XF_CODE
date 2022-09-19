<?php
/**
 * @author liuzhenpeng
 * @abstract 密保问题校验手机验证码
 * @date 2015-09-08
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\LogRegLoginService;
use core\service\bonus;
use libs\utils\Logger;

class DoCheckProtectionMobile extends BaseAction {

    private $_errorCode = 0;
    private $_errorMsg = null;

    public function init() {
        $protectionResult = array("errorCode" => 0, "errorMsg" => '');
        $this->form = new Form('post');
        $this->form->rules = array(
            "code" => array("filter" => "required", "message" => "code is required"),
        );

        if (!$this->form->validate()) {
            $protectionResult['errorCode'] = -1;
            $protectionResult['errorMsg'] = $this->form->getErrorMsg();
            echo json_encode($protectionResult);
            return false;
        }
    }

    public function invoke() {
        $user_id = intval($GLOBALS['user_info']['id']);
        if(!$user_id){
            $protectionResult['errorCode'] = -2;
            $protectionResult['errorMsg']  = "发生系统错误";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }
        $this->form->data['code']   = (string)$this->form->data['code'];
        $this->form->data['mobile'] = $GLOBALS['user_info']['mobile'];
        
        // 是否开启验证码效验
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($this->form->data['mobile'], 180, 0));
            if ($vcode !== $this->form->data['code']) {
                $_SESSION['user_protion_is_mobile'] = 0;
                $loginResult['errorCode'] = -3;
                $loginResult['errorMsg'] = "验证码错误";
                setLog($loginResult);
                die(json_encode($loginResult));
            }else{
                $this->rpc->local('MobileCodeService\delMobileCode', array($this->form->data['mobile'],0));
                $_SESSION['user_protion_is_mobile'] = 1;
            }
        }
        $protectionResult['errorCode'] = 0;
        $protectionResult['url']       = "/account/ProtectPwd";
        $protectionResult['errorMsg']  = "ok";
        echo json_encode($protectionResult);
    }

} 
