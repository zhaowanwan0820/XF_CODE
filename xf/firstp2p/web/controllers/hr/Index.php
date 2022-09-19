<?php

namespace web\controllers\hr;

use web\controllers\BaseAction;
use core\service\WeiXinService;

class Index extends BaseAction {

    public function invoke() {
        $wxService = new WeiXinService();
        $wxShareData = $wxService->getJsApiSignature();
        $this->tpl->assign('wxShareData', $wxShareData);
        $this->template = 'web/views/v3/hr/index.html';
    }

}
