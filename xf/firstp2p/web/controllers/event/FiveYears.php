<?php
/**
 * Five Years Old 
 */
namespace web\controllers\event;

use web\controllers\BaseAction;
use core\service\WeiXinService;

class FiveYears extends BaseAction 
{
    public function init()
    {
    }

    public function invoke()
    {
        $isApp = isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) > 100 ? 1 : 0;
        // 微信分享js签名
        $wxService = new WeiXinService();
        $isWeixin = $wxService->isWinXin();

        $this->tpl->assign("isApp", $isApp);

        $jsApiSingature = $wxService->getJsApiSignature();
        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);
        $this->template = 'web/views/v3/event/fiveyears.html';
    }
}


