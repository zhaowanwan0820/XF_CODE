<?php
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

/**
 * 登出接口
 * @author longbo
 */
class Logout extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_MANUAL_REASON', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $ret = $this->rpc->local("UserTokenService\deleteToken", array($data['token']));
        $this->json_data = [];
        return true;
    }

}
