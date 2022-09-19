<?php

/**
 * CheckRegisterCode.php
 * 注册时 校验短信验证码
 * @date 2016-10-31
 * @author yanjun <yanjun5@ucfgroup.com>
 */

namespace api\controllers\enterprise;

use libs\web\Form;
use api\controllers\enterprise\DoRegister;

class CheckRegisterCode extends DoRegister {

    public function init() {
        // 获取基类
        $grandParent = self::getRoot();
        $grandParent::init();

        $this->form = new Form("post");
        $this->form->rules = array(
            'phone' => array("filter" => 'required'),
            'code' => array("filter" => 'required'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }

        if (!$this->check_phone($this->form->data['phone'])) {
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;

        // 测试环境方便自动化,忽略短验
        if (false === $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $this->json_data = true;
            return;
        }

        $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($params['phone'], '', 0));
        if ($vcode == $params['code']) {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $redis->set($this->prefix_key.$params['phone'], $vcode);
            $this->json_data = true;
        } else {
            $this->setErr('ERR_SIGNUP_CODE');
            return ;
        }
    }

}
