<?php
/**
 * 父亲节H5页面
 */

namespace web\controllers\event;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\WeiXinService;

class FatherDay extends BaseAction {

    public function init() {

    }   

    public function invoke() {

        $wxService = new WeiXinService();
        $jsApiSingature = $wxService->getJsApiSignature();

        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);

        $isApp = isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) > 100 ? 1 : 0;
        $this->tpl->assign("isApp", $isApp);

        $this->template = "web/views/event/father_day.html";
    }

}
