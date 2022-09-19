<?php

/**
 * CheckRegister.php
 * 注册时  校验手机号和邀请码
 * @date 2016-10-30
 * @author yanjun <yanjun5@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;

class CheckRegister extends Signup {

    public function init() {
        // 获取基类
        $grandParent = self::getRoot();
        $grandParent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'phone' => array("filter" => 'required'),
            'invite' => array("filter" => 'string'),
            'country_code' => array("filter" => "string",'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }

        if(!$this->check_phone()){
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;

        //校验手机号是否注册过
        $result = $this->rpc->local('UserService\checkUserMobile', array($params['phone']));
        if (!empty($result) && !isset($result['code'])) {
            $this->json_data = $result;
        } else{
            if($result['code'] == 320){
                $this->setErr('ERR_FAILED_RESETPWD', $result['reason']);
            }else{
                $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
            }
            return ;
        }

        //校验邀请码是否有效
        if(!empty($params['invite'])){
            $result = $this->check_invite();
            return ;
        }
    }

}
