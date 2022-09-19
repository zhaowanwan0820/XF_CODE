<?php

/**
 * 企业用户投资提示
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\deal;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\H5UnionService;
use core\service\RegisterService;

class PromptCompany extends BaseAction {
    public function init()
    {
    }

    public function invoke()
    {
        return $this->tpl->display('promptcompany.html');
    }
}