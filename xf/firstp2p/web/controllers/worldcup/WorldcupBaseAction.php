<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\BaseAction;

/**
 * 2018世界杯活动
 */
class WorldcupBaseAction extends BaseAction {

    public $isApp = false;

    protected function _check_login() {
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
            $location_url = !empty($current_url) ? "user/login?tpl=worldcup&backurl=" . $current_url : "user/login?tpl=worldcup";
            set_gopreview();
            return app_redirect(url($location_url));
        }
        return true;
    }

    protected function _getUserImage($userId, $mobile) {
        //获取用户微信头像
        $avatar = $this->rpc->local('UserImageService\getUserImageInfo', array($userId));
        $result['avatar'] = '';
        $avatarFrom = '';//记录用户头像来源
        if ($avatar && !empty($avatar['attachment'])) {
            $avatarFrom = 'UserImageService本地用户头像';
            if (stripos($avatar['attachment'], 'http') === 0) {
                $result['avatar'] = $avatar['attachment'];
            } else {
                $result['avatar'] = 'http:' . (isset($GLOBALS['sys_config']['STATIC_HOST']) ? $GLOBALS['sys_config']['STATIC_HOST'] : '//static.firstp2p.com') . '/' . $avatar['attachment'];
            }
        } else {
            $avatar = $this->rpc->local('UserProfileService\getUserHeadImg', array($mobile));
            if (!empty($avatar['headimgurl']) && stripos($avatar['headimgurl'], 'http') === 0) {
                $avatarFrom = 'UserProfileService调用的微信用户头像';
                $result['avatar'] = $avatar['headimgurl'];
            }
        }
        return $result['avatar'];
    }

    public function _after_invoke() {
        $isApp = isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) > 100 ? 1 : $this->isApp;
        $this->tpl->assign("isApp", $isApp);
        parent::_after_invoke();
    }
}
