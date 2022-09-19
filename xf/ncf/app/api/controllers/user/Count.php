<?php

/**
 * 我的页面中，未使用红包、礼券、投资券和风险评估的信息
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\UserService;

class Count extends AppBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "token" => array(
                "filter" => "required",
                "message" => "ERR_GET_USER_FAIL"
            )
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        // 这里需要完善普惠的数据获取逻辑
        $loginUser = $this->user;
        $result = UserService::userCount($loginUser['id']);
        if ($result === false) {
            $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
        }

        // 红包
        $result['new_bonus_title'] = app_conf('NEW_BONUS_TITLE');
        $result['new_bonus_unit'] = app_conf('NEW_BONUS_UNIT');
        $this->json_data = $result;
    }
}