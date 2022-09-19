<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class AjaxLog extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int'),
            'pageSize' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $token = $data['token'];
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $page = isset($data['page']) ? $data['page'] : 1;
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : 10;

        $response = $this->rpc->local('BonusService\getBonusLogList', [$loginUser['id'], $page, $pageSize]);
        $res['list'] = $response['list'];
        $res['count'] = $response['page']['total'];

        $this->json_data = $res;
    }

}
