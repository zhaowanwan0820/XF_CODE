<?php
/**
 * 在线报名
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2016-12-02
 */
namespace web\controllers\Activity;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\WeiXinService;
use core\dao\vip\ActivityUserModel;
use core\dao\vip\ActivityModel;
use core\dao\vip\VipAccountModel;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

class OnlineRegistration extends BaseAction
{
    public $isApp = false;

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'from_login' => array('filter' => 'string'),
            'isApp' => array('filter' => 'string'),
            'activity_id' => array('filter' => 'required'),
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $data = $this->form->data;
        $token = $data['token'];

        $this->isApp = intval($data['isApp']);
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        $userId = $GLOBALS['user_info']['id'];
        $activityId = intval($data['activity_id']);

        if (!$this->_check_login()) {
            return false;
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


        $activityModel = new ActivityModel;
        $activityInfo = $activityModel->getActivityById($activityId);
        //获取微信分享图标和文案
        $this->tpl->assign('sharedIcon',$activityInfo['shared_icon']);
        $this->tpl->assign('sharedText',$activityInfo['shared_text']);
        $this->tpl->assign('shareLink', '/activity/OnlineRegistration');

        $this->tpl->assign('activityId',$activityId);
        $this->tpl->assign('title',$activityInfo['title']);
        $this->tpl->assign('detail',$activityInfo['detail']);

        //判断活动是否结束
        if (time() > intval($activityInfo['end_time'])) {
            $this->tpl->assign("type",1);
            $this->template = 'web/views/v3/activity/onlinereg_error.html';
            return false;
        }

        //比较用户等级与活动限制等级
        $vipAccountModel = new VipAccountModel;
        $vipAccountInfo = $vipAccountModel->getVipAccountByUserId($userId);
        if (intval($vipAccountInfo['service_grade']) < intval($activityInfo['level'])) {
            $this->tpl->assign("type",2);
            $this->tpl->assign("userLevel",VipEnum::$vipGrade[intval($activityInfo['level'])]['name']);
            $this->template = 'web/views/v3/activity/onlinereg_error.html';
            return false;
        }

        //判断用户是否参加过此活动
        $activityUserModel = new ActivityUserModel;
        $activityUserInfo = $activityUserModel->isRegistration($userId,$activityId);
        if ($activityUserInfo) {
            $this->tpl->assign("type",3);
            $this->template = 'web/views/v3/activity/onlinereg_error.html';
            return false;
        }

        $this->tpl->assign("name",$GLOBALS['user_info']['real_name']);
        $this->template = 'web/views/v3/activity/online_registration.html';
        return true;
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
            $location_url = !empty($current_url) ? "user/login?tpl=onlineregistration&backurl=" . $current_url : "user/login?tpl=onlineregistration";
            set_gopreview();
            return app_redirect(url($location_url));
        }
        return true;
    }
}
