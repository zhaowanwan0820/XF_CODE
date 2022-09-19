<?php
/**
 * 公益宣传页面
 *
 * @date 2015-12-10
 */

namespace web\controllers\event;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\WeiXinService;

class Gongyi extends BaseAction {

    public function init() {

    }   

    public function invoke() {

        $wxService = new WeiXinService();
        $jsApiSingature = $wxService->getJsApiSignature();

        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);
        $this->tpl->assign('wxShareTitle', app_conf('GONGYI_PROPAGANDA_WEIXIN_SHARE_TITLE'));
        $this->tpl->assign('wxShareDesc', app_conf('GONGYI_PROPAGANDA_WEIXIN_SHARE_DESC'));
        $this->tpl->assign('wxShareImg', app_conf('GONGYI_PROPAGANDA_WEIXIN_SHARE_IMG'));
        $this->tpl->assign('currentUrl','http://'.APP_HOST.'/event/gongyi');
        $this->template = "web/views/v2/event/gongyi.html";
    }

}
