<?php

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\WeiXinService;
use NCFGroup\Protos\O2O\Enum\GameEnum;

/**
 * 游戏活动平台统一入口
 */
class Game extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'event_id' => array('filter' => 'required', "message" => '活动id不能为空'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $token = $data['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        if (!$this->_check_login()) {
            return false;
        }

        $uid = intval($GLOBALS['user_info']['id']);
        // 获取游戏内容详情
        $error = '';
        $event = $this->rpc->local('GameService\getEventDetail', array($uid, $data['event_id']));
        if ($event === false) {
            $error = $this->rpc->local('GameService\getErrorMsg');
            $event = GameEnum::$DEFAULT_EVENT_DETAIL;
        }

        $isApp = isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) > 100 ? 1 : 0;
        $isShare = $isApp;
        if ($isShare && isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) <= 440
            && isset($_SERVER['HTTP_OS']) && strtolower(trim($_SERVER['HTTP_OS'])) != 'android') {
            $isShare = 0;
        }

        // 微信分享js签名
        $wxService = new WeiXinService();
        $isWeixin = $wxService->isWinXin();

        $this->tpl->assign("isApp", $isApp);
        $this->tpl->assign('isShare', $isShare || $isWeixin);
        $this->tpl->assign("token", $token);

        $jsApiSingature = $wxService->getJsApiSignature();
        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);

        $this->tpl->assign('eventId', isset($data['event_id']) ? trim($data['event_id']) : '');
        $this->tpl->assign('event', $event);
        $this->tpl->assign('mobile', $GLOBALS['user_info']['mobile']);
        $this->tpl->assign('errors', $error);

        // 选择游戏模板
        $this->template = 'web/views/v3/game/'.$event['gameTemplate'].'.html';
    }

    private function _check_login() {
        if (empty($GLOBALS['user_info'])) {
            $url = 'http://';
            if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
                || $_SERVER['SERVER_PORT'] == '443') {
                $url = 'https://';
            }
            if ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
                $url .= $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
            } else {
                $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            }

            $current_url = urlencode($url);
            $location_url = !empty($current_url) ? "user/login?tpl=game&backurl=" . $current_url : "user/login?tpl=game";
            set_gopreview();
            return app_redirect(url($location_url));
        }
        return true;
    }
}