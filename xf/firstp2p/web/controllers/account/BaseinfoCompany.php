<?php

/**
 * 企业用户注册页面
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\H5UnionService;
use core\service\RegisterService;


class BaseinfoCompany extends BaseAction {
    public function init()
    {
    }

    public function invoke()
    {
        return $this->tpl->display('baseinfocompany.html');
    }

}
