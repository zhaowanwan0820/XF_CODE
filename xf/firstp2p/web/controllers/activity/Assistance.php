<?php

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\WeiXinService;
use NCFGroup\Common\Library\ApiService;
use libs\utils\Aes;
use core\service\marketing\AssistanceService;
use libs\weixin\Weixin;
use core\dao\BonusConfModel;

class Assistance extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'app_version' => array('filter' => 'string'),
            'eventId' => array('filter' => 'string'),
            'sn' => array('filter' => 'string')
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $token = $this->form->data['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        $eventId = $this->form->data['eventId']; // 活动ID
        $assistanceService = new AssistanceService($eventId);
        if ($eventId == '' || empty($assistanceService->config)) {
            $this->template = 'web/views/v3/activity/error_assistance.html';
            return;
        }

        list($startTime, $endTime) = $assistanceService->config['time_scope'];
        if ($startTime > $_SERVER['REQUEST_TIME'] || $endTime < $_SERVER['REQUEST_TIME']) {
            $this->tpl->assign('pageType', 9);
            $this->template = 'web/views/v3/activity/assistance.html';
            return;
        }

        $appid = BonusConfModel::get('XINLI_WEIXIN_APPID');
        $secret = BonusConfModel::get('XINLI_WEIXIN_APPSECRET');

        $options = array(
            'appid' => $appid,
            'appsecret' => $secret,
        );
        $weObj = new Weixin($options);
        $nonceStr = md5(time());
        $timeStamp = time();
        $currentUrl = get_domain().$_SERVER["REQUEST_URI"];
        $signature = $weObj->getJsSign($currentUrl, $timeStamp, $nonceStr, $appid);

        $this->tpl->assign('appid', $appid);
        $this->tpl->assign('timeStamp', $timeStamp);
        $this->tpl->assign('nonceStr', $nonceStr);
        $this->tpl->assign('signature', $signature);
        $this->tpl->assign('shareTitle', $assistanceService->config['share_title']);
        $this->tpl->assign('shareContent', $assistanceService->config['share_content']);
        $this->tpl->assign('shareIcon', $assistanceService->config['share_icon']);
        $this->tpl->assign('shareUrl', $currentUrl);

        if (empty($GLOBALS['user_info'])) {
            $eventUrl = get_domain().str_replace('Assistance', 'DoAssistance', $_SERVER['REQUEST_URI']);
            $this->tpl->assign('assistanceUrl', $eventUrl);
            $this->tpl->assign('isLogin', 0);
        } else {
            $this->tpl->assign('isLogin', 1);
            $sn = $this->form->data['sn'];
            if ($sn == '') {
                $ownerUid = $GLOBALS['user_info']['id'];
            } else {
                $ownerUid = Aes::decryptHex($sn, AssistanceService::SN_KEY);
            }
            $role = $GLOBALS['user_info']['id'] == $ownerUid ? 0 : 1;
            if ($ownerUid <= 0) {
                $this->template = 'web/views/v3/activity/error_assistance.html';
                return;
            }
            $data = $assistanceService->getUserEventInfo($ownerUid, $GLOBALS['user_info']['id'], $GLOBALS['user_info']['mobile'], $role);
            foreach ($data as $key => $value) {
                $this->tpl->assign($key, $value);
            }
        }
        $this->template = 'web/views/v3/activity/assistance.html';
    }

}


