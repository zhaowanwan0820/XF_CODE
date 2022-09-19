<?php

/**
 * 登录接口
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class LoginCunguan extends AppBaseAction {

    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->h5View = true;
        $this->app_version = 500;
        $_SERVER['HTTP_VERSION'] = 500;
    }

    public function invoke() {
        exit('no permission');
    }

}
