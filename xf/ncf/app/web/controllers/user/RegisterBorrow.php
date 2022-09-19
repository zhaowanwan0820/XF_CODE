<?php

/**
 * 第三方借款用户注册页面
 * @author luzhengshuai@ucfgroup.com
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use core\enum\UserAccountEnum;

class RegisterBorrow extends BaseAction {

    // 直接跳转到注册页
    public function init() {
        return app_redirect('/user/register?purpose=' . UserAccountEnum::ACCOUNT_FINANCE);
    }

}
