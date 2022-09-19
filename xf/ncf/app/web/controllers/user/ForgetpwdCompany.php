<?php

/**
 * 企业用户找回密码
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\H5UnionService;
use core\service\RegisterService;

error_reporting(E_ALl);
ini_set('display_errors', 1);

class ForgetpwdCompany extends BaseAction {
    public function init()
    {
    }

    public function invoke()
    {
        return $this->tpl->display('forgetpwdcompany.html');
    }

}
