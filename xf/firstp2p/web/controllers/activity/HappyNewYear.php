<?php

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\WeiXinService;
use NCFGroup\Protos\Medal\RequestMedalUser;

/**
 * 感恩投资券大派送活动
 */
class HappyNewYear extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'from_login' => array('filter' => 'string'),
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
        $create_time = intval($GLOBALS['user_info']['create_time']);
        // 注册时间，折算成天
        $regDays = ceil((time() - $create_time) / 86400);

        // 获取用户的投资数据
        $investData = $this->rpc->local("HappyNewYearService\getUserTotalInvestMoney", array($uid));
        $this->tpl->assign("investAmount", $investData['load_money']);

        $isHide = isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) < 333
            && isset($_SERVER['HTTP_OS']) && strtolower(trim($_SERVER['HTTP_OS'])) == 'android' ? 1 : 0;

        $isApp = isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) > 100 ? 1 : 0;

        $this->tpl->assign("isApp", $isApp);
        $this->tpl->assign("isHideShare", $isHide);
        $this->tpl->assign("token", $token);
        // 微信分享js签名
        $wxService = new WeiXinService();
        $jsApiSingature = $wxService->getJsApiSignature();
        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);

        $GLOBALS['jsApiSingature'] = $jsApiSingature;

        // 获取用户的勋章
        $request = new RequestMedalUser();
        $request->setUserId(intval($uid));
        $medals = $this->rpc->local('MedalService\getUserMedalList', array($request));
        // 获取用户获得的奖励
        $package = $this->rpc->local('HappyNewYearService\getNewYearPackage', array($uid, $regDays, $investData['load_money']));
        $GLOBALS['medals'] = $medals;
        $GLOBALS['medalCount'] = $medals ? count($medals) : 0;
        $this->tpl->assign('rewards', $package);
        $this->tpl->assign("medalCount", $GLOBALS['medalCount']);
        $this->tpl->assign("regTime", $create_time);
        $this->tpl->assign('regDays', $regDays);
        $this->tpl->assign("num", $uid);
        $this->tpl->assign('mobile', $GLOBALS['user_info']['mobile']);
        $this->tpl->assign('fromLogin', isset($this->form->data['from_login']) ? $this->form->data['from_login'] : 0);
        $this->template = 'web/views/v3/happy_newyear/index.html';
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

            // 增加登录来源判断
            if (strpos($url, 'from_login') === false) {
                if (strpos($url, '?') !== false) {
                    $url .= '&from_login=1';
                } else {
                    $url .= '?from_login=1';
                }
            }

            $current_url = urlencode($url);
            $location_url = !empty($current_url) ? "user/login?tpl=happynewyear&backurl=" . $current_url : "user/login?tpl=happynewyear";
            set_gopreview();
            return app_redirect(url($location_url));
        }
        return true;
    }

}
