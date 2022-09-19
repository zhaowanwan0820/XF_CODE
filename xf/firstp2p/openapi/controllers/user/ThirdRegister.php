<?php
/**
 * openapi 第三方注册接口
 * @author longbo
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\lib\Tools;


class ThirdRegister extends BaseAction {


    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'mobile' => array(
                'filter' => 'required', 
                "message" => "手机号码应为7-11为数字",
                'option' => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/")
                ),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", "Mobile is error");
            return false;
        }
    }

    public function invoke() {

        try{
            $result = $this->rpc->local('UserService\signupForJF', array($this->form->data['mobile']));
            $user_id = $result;
            $openId = Tools::getOpenID($user_id);
            $this->json_data = array('openId' => $openId);
        } catch (\Exception $exc) {
            $this->errorCode = ($exc->getCode()=== 0) ? -1 : $exc->getCode();
            $this->errorMsg = $exc->getMessage();
            return false;
        }
        
    }


}
