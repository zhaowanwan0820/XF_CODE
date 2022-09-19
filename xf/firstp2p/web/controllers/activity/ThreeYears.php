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

class ThreeYears extends BaseAction 
{
    const TOTAL = 7000000;
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

        $uid = intval($GLOBALS['user_info']['id']);
        $create_time = intval($GLOBALS['user_info']['create_time']);
        $reg_time = ceil((time() - $create_time) / (3600*24));
        $isNew = 0;
        if ($create_time > strtotime("2016-07-11 00:00:00")) {
            $isNew = 1;
        } else {
            $reg_num = number_format(((self::TOTAL - $uid) / self::TOTAL) * 100, 2); 
            $reg_num = ($reg_num == '100.00') ? '99.99' : $reg_num;

            $data = $this->rpc->local("UserGrowthService\getUserThreeYearsData", array($uid));
            $this->tpl->assign("regNum", $reg_num);
            $this->tpl->assign("investAmount", $data['invest_amount']);
            $this->tpl->assign("investRank", $data['invest_rank']);
            $this->tpl->assign("bonusGet", $data['bonus_get']);
            $this->tpl->assign("medalCount", $data['medal_count']);
            $this->tpl->assign("medalRank", $data['medal_rank']);
            $this->tpl->assign("medalPerc", $data['medal_perc']);

        }
        $app = $this->form->data['app_version'];
        $isHide = $this->form->data['is_hide'] ?: 0;
        $isApp = intval($app) > 100 ? 1 : 0;
        $this->tpl->assign("isApp", $isApp);
        $this->tpl->assign("isHideShare", $isHide);

        $wxService = new WeiXinService();
        $jsApiSingature = $wxService->getJsApiSignature();
        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);

        $this->tpl->assign("regTime", $reg_time);
        $this->tpl->assign("num", $uid);
        $this->tpl->assign("isNew", $isNew);
        $this->template = 'web/views/v3/activity/threeyears.html';
    }

    private function _check_login()
    {
        if (empty($GLOBALS ['user_info'])) {
            $current_url = urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $location_url = !empty($current_url) ? "user/login?tpl=threeyears&backurl=" . $current_url : "user/login?tpl=threeyears";
            set_gopreview();
            return app_redirect(url($location_url));
        }
        return true;
    }

}


