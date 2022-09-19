<?php
namespace core\event;

use libs\utils\Logger;
use core\dao\BonusConfModel;
use core\event\BaseEvent;
use core\service\WeiXinService;
use libs\utils\Curl;
use NCFGroup\Common\Library\SignatureLib;

class WeixinBindEvent extends BaseEvent
{

    public function __construct($wxId, $openId, $userId)
    {
        $this->wxId = $wxId;
        $this->openId = $openId;
        $this->userId = $userId;
    }

    public function execute()
    {
        $wxSrv = new WeiXinService;
        $res = $wxSrv->insertWeixinBind([
            'openid' => $this->openId,
            'weixin_id' => $this->wxId,
            'user_id' => $this->userId,
        ]);
        Logger::info(implode('|', [__METHOD__, $res, $this->openId, $this->wxId, $this->userId]));

        if ($res) {
            if (!WeiXinService::syncBind2CallCenter($this->userId, $this->openId, 'add')) {
                // 如果失败，直接通知微信端绑定成功，走普通客服逻辑
                $wxSrv->bindSuccessCallback($this->openId);
            }
        }
        return $res;
    }

    public function alertMails()
    {
        return array('zhangzhuyan@ucfgroup.com');
    }

}
