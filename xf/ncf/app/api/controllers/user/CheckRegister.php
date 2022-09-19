<?php

/**
 * CheckRegister.php
 * 注册时  校验手机号、邀请码和活动编码
 */

namespace api\controllers\user;

use libs\web\Form;
use core\service\user\UserService;

class CheckRegister extends Signup {
    public function init() {
        // 获取基类
        $grandParent = self::getRoot();
        $grandParent::init();

        $this->form = new Form("post");
        $this->form->rules = array(
            'phone' => array("filter" => 'required'),
            'password' => array("filter" => 'required'),
            'verify' => array("filter" => 'string'),
            'invite' => array("filter" => 'string'),
            'site_id' => array("filter" => 'string'),
            'euid' => array("filter" => 'string'),
            'country_code' => array("filter" => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        // 参数校验
        if (!$this->check_phone() && !$this->check_password()) {
            return false;
        }

        $data = $this->form->data;
        // 对于wap的特殊处理
        if ($this->isWapCall()) {
            // 校验图形验证码
            $sessionId = session_id();
            $cache = \SiteApp::init()->cache;
            $verify = $cache->get("verify_" . $sessionId);
            $cache->delete("verify_" . $sessionId);
            $data['verify'] = strtolower(trim($data['verify']));
            if ($verify != md5($data['verify'])) {
                $this->setErr('ERR_VERIFY_ILLEGAL');
                return false;
            }
        }

        // 校验手机号是否注册过
        $result = UserService::userCheckRegister($data);
        if ($result === false) {
            $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
            return false;
        }

        $this->json_data = $result;
    }
}
