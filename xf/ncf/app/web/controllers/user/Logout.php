<?php
/**
 * 新版用户登出
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\UserLoginService;

class Logout extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'callback'=>array("filter"=>'string'),
            'backurl' =>array("filter"=>'url'),
        );
        $this->form->validate();
    }

    public function invoke() {
        if (empty($this->form->data['backurl']))
        {
            $this->form->data['backurl'] = get_domain();
        }

        // 退出登录
        UserLoginService::setUserLogout();

        if (!empty($this->form->data['backurl']) && isMainDomain($this->form->data['backurl'])) {
            header("Location:" . urldecode(trim($this->form->data['backurl'])));
        }
        return true;
    }
}