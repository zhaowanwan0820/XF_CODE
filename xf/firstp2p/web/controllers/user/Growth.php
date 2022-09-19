<?php
/**
 * 成长轨迹
 * @author longbo
 */
namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\WeiXinService;

class Growth extends BaseAction 
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'app_version' => array('filter' => 'string'),
            'is_hide' => array('filter' => 'string'),
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
        $this->_check_login();
        $userGrowth = $this->rpc->local("UserGrowthService\getUserGrowth", array($GLOBALS['user_info']['id']));
        $isInvestor = 1;
        if (empty($userGrowth)) {
            $user_name = empty($GLOBALS['user_info']['real_name']) ? $GLOBALS['user_info']['user_name'] : $GLOBALS['user_info']['real_name'];
            $userGrowth = array('real_name' => $user_name);
            $isInvestor = 0;
        }
        $app = $this->form->data['app_version'];
        $isHide = $this->form->data['is_hide'] ?: 0;
        $isApp = intval($app) > 100 ? 1 : 0;

        $this->tpl->assign("userGrowth", json_encode($userGrowth));
        $this->tpl->assign("isInvestor", $isInvestor);
        $this->tpl->assign("isApp", $isApp);
        $this->tpl->assign("isHideShare", $isHide);

        $wxService = new WeiXinService();
        $jsApiSingature = $wxService->getJsApiSignature();

        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);

        $this->template = 'web/views/user/growth.html';
    }

    private function _check_login()
    {
        if (empty($GLOBALS ['user_info'])) {
            $current_url = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $location_url = !empty($current_url) ? "user/login?tpl=growth&backurl=" . $current_url : "user/login?tpl=growth";
            set_gopreview();
            return app_redirect(url($location_url));
        }
        return true;
    }

}


