<?php
/**
 * Three Years Old
 * @author longbo
 */
namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\WeiXinService;
use core\service\marketing\BlessService;
use core\service\WeixinInfoService;

class Bless extends BlessBase
{

    public function invoke()
    {
        if (!$this->checkTime()) {
            $this->template = "web/views/activity/fouryearsend.html";
            return true;
        }
        if ($this->blessId) {
            $res = (new BlessService)->getInfo($this->blessId);

            $info = $res['info'];
            $upvoteInfo = $res['upvote'];

            $isUpvoted = false;
            foreach ($upvoteInfo as $k => $item) {
                if (!$isUpvoted && $this->openid == $item['openId']) {
                    $isUpvoted = true;
                }
                $wxUserInfo = WeixinInfoService::getWeixinInfo($item['openId']);
                $upvoteInfo[$k]['detail'] = $wxUserInfo['user_info'];
            }

            $userInfo = WeixinInfoService::getWeixinInfo($info['openId']);

            $this->tpl->assign('info', $info['info']);
            $this->tpl->assign('userInfo', $userInfo['user_info']);
            $this->tpl->assign('upvoteInfo', $upvoteInfo);
            $this->tpl->assign('isUpvoted', $isUpvoted);
            $this->tpl->assign('cnt', 3 - count($upvoteInfo));
            $this->tpl->assign('sn', $this->sn);
            $this->template = "web/views/activity/fouryearssuc.html";
        } else {
            $this->template = "web/views/activity/fouryears.html";
        }
    }

}


