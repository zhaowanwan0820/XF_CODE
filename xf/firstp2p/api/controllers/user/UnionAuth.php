<?php
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

/**
 * 联合登录用户认证接口
 * @author longbo
 */
class UnionAuth extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => '登录过期，请重新登录'),
            'clientId' => array('filter' => 'required', 'message' => 'clientId不能为空'),
            'scope' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_MANUAL_REASON', $this->form->getErrorMsg());
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
        $ret = $this->rpc->local("UserBindService\getUnionToken",
            array(
                'uid' => $loginUser['id'],
                'client_id' => $data['clientId'],
                'scope' => $data['scope'] ?: '',
            )
        );

        $result = array();
        if ($ret->accessToken) {
            $result['accessToken'] = $ret->accessToken;
            $result['expires'] = $ret->expires ?: 0;
        }

        $this->json_data = $result;
        return true;
    }

}
