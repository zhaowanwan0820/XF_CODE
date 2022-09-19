<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\ConstDefine;

/**
 * BindCard
 * 支付绑卡接口
 *
 * @uses BaseAction
 * @package
 * @version $id$
 */
class BindCard extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        //$this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $loginUser['id']));
        if (empty($bankcard['verify_status'])) {
            $url = sprintf(
                    $this->getHost()."/payment/Transit?params=%s",
                    urlencode(json_encode(['srv' => 'bindcard', 'reqSource' => 1]))
                    );
        } else {
            $this->setErr('ERR_MANUAL_REASON', '验卡已成功,请刷新');
            return false;
        }

        $this->json_data = ['h5AuthCardUrl' => $url];
        return true;
    }
}

