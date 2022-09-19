<?php
/**
 * 成长轨迹
 * @author longbo
 */
namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\WeiXinService;

class Growth2018 extends BaseAction
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
        $userGrowth = $this->rpc->local("UserGrowth2018Service\getUserGrowth", array($GLOBALS['user_info']['id']));
        $isInvestor = 1;
        if (empty($userGrowth)) {
            $isInvestor = 0;
        }
        $regTime = $GLOBALS['user_info']['create_time'];
        list($year, $month, $day) = explode('-', date('Y-m-d', $regTime + 28800));
        $userGrowth["total_days"] = ceil((time() - mktime(0, 0, 0, $month, $day, $year)) / 86400);
        //数据
        $this->tpl->assign("user_growth", $userGrowth);
        //是否是投资用户
        $this->tpl->assign("isInvestor", $isInvestor);
        $app = $this->form->data['app_version'];
        $isHide = $this->form->data['is_hide'] ?: 0;
        $isApp = intval($app) > 100 ? 1 : 0;
        $this->tpl->assign("is_app", $isApp);
        $this->tpl->assign("is_hide_share", $isHide);

        $wxService = new WeiXinService();

        $jsApiSingature = $wxService->getJsApiSignature();

        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);

        $this->template = 'web/views/user/growth2018.html';
    }

    private function _check_login($tpl = 'growth2018')
    {
        if (empty($GLOBALS ['user_info'])) {
            $currentUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $locationUrl = !empty($currentUrl) ? "user/login?tpl={$tpl}&backurl=" . $currentUrl : "user/login?tpl={$tpl}";
            set_gopreview();
            return app_redirect(url($locationUrl));
        }
        return true;
    }
}


