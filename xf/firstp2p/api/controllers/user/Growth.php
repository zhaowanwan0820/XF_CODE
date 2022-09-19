<?php
/**
 * 成长轨迹
 * @author longbo
 */
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Growth extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $data = $this->form->data;
        $weburl = $GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'];
        $token = $data['token'];
        $os = strtolower(trim($_SERVER['HTTP_OS']));
        $isHide = 0;
        if ($this->app_version < 333 && $os == 'android') {
            $isHide = 1;
        }
        $url = 'http://'.$weburl.'/user/growth?app_version='.$this->app_version.'&is_hide='.$isHide;
        if (empty($token)) {
            app_redirect($url);
            exit;
        } else {
            app_redirect($url.'&token='.$token);
            exit;
        }
    }
}


