<?php

namespace openapi\controllers\verify;

use libs\web\Form;
use openapi\controllers\BaseAction;

class TokenVerify extends BaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'w'  => array('filter' => 'int', 'option' => array('optional' => true), 'message' => '参数格式不正确'),
            'h'  => array('filter' => 'int', 'option' => array('optional' => true), 'message' => '参数格式不正确'),
            'rb' => array('filter' => 'int', 'option' => array('optional' => true), 'message' => '参数格式不正确'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $this->setCaptcha($this->form->data);
        exit;
    }

    public function setCaptcha($property) {
        error_reporting(E_ALL);

        require_once APP_ROOT_PATH . "system/utils/es_session.php";
        require_once APP_ROOT_PATH . "system/utils/es_image.php";

        $verify  = $this->authTokenCaptchaKey($property['oauth_token']);
        $rand_bg = isset($property['rb']) ? ($property['rb'] == '0') ? 0 : 1 : 1;

        $w = isset($property['w']) ? intval($property['w']) : 50;
        $h = isset($property['h']) ? intval($property['h']) : 22;

        $w = $w > 100 ? 100 : $w;
        $h = $h > 50 ? 50 : $h;

        \es_image::buildImageVerify(4, 1, 'gif', $w, $h, $verify, $rand_bg, false);
    }

}
