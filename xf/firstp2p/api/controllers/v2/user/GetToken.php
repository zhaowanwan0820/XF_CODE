<?php

/**
  * 自定义设置token的过期时间
  */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\UserTokenService;

class GetToken extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'user_id' => array('filter' => 'required'),
            'expire_time' => array('filter' => 'int'),//token过期时间
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $tokenExpireTime = intval($params['expire_time']) > 0 ? intval($params['expire_time']) : UserTokenService::API_TOKEN_EXPIRE;
        $token = $this->rpc->local("UserTokenService\getApiToken", array($params['user_id'], $tokenExpireTime));
        $this->json_data = array(
            'token' => $token,
            'tokenExpireTime' => (time() + $tokenExpireTime),
        );
   }

}
