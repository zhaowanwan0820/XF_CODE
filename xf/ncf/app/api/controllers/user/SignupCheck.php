<?php

/**
 * SignupCheck.php
 *
 * @date 2014-05-05
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use libs\utils\Logger;
use core\service\user\UserService;

class SignupCheck extends Signup {

    public function init() {
        // 获取基类
        $grandParent = self::getRoot();
        $grandParent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'phone' => array("filter" => 'required'),
            'password' => array("filter" => 'string'),
            'invite' => array("filter" => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }

        if (!empty($this->form->data['password']) && !$this->check_password()) {
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $result = UserService::checkUserMobile($params['phone']);

        // 为空说明访问wxuser失败或者mobile为空
        if (!isset($result['isExist'])) {
            $this->setErr('ERR_SYSTEM_CALL_CUSTOMER');
        }

        if ($result['isExist']) {
            $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
        }

        //app 3.5版本 增加弱密码校验
        $mobile = $params['phone'];
        $password = $params['password'];
        //获取密码黑名单
        \FP::import("libs.common.dict");
        $blacklist = \dict::get("PASSWORD_BLACKLIST");

        //基本规则判断
        $base_rule_result = login_pwd_base_rule(strlen($password), $mobile, $password);
        if ($base_rule_result) {
            $this->setErr('ERR_PASS_RULE', $base_rule_result['errorMsg']);
        }

        //黑名单判断,禁用密码判断
        $forbid_black_result = login_pwd_forbid_blacklist($password, $blacklist, $mobile);
        if ($forbid_black_result) {
            $this->setErr('ERR_PASS_BLACKLIST', $forbid_black_result['errorMsg']);
        }
        //密码校验结束

        $this->json_data = true;
    }
}
