<?php

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;

class Session extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'code' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $code = trim($data['code']);
        if (empty($code)) {
            return false;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $sessionId = $redis->get($code);
        if (empty($sessionId)) {
            return false;
        }

        header("P3P: CP=CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR");
        if (setcookie('PHPSESSID', $sessionId, 0, '/', '', false, true)) {
            return $redis->del($code);
        }

        return false;
    }

}
