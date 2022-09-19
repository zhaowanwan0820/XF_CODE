<?php

namespace NCFGroup\Ptp\Apis;


use core\service\WeiXinService;

/**
 * 微信分享信息接口
 */
class WeishareApi
{
    public function msg() {
        $Weixinservice = new WeiXinService();
        $di = getDI();
        $this->params = $di->get('requestBody');
        $frontend_url = $this->params['frontend_url'];
        $message = $Weixinservice->getJsApiSignature($frontend_url);
        return $message;
    }
}

