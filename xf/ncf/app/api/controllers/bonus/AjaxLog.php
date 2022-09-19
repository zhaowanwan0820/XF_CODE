<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\bonus\BonusService;

class AjaxLog extends AppBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_GET_USER_FAIL'),
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
        $loginUser = $this->user;

        $page = isset($data['page']) ? $data['page'] : 1;
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : 10;

        $response = BonusService::getBonusLogList($loginUser['id'], $page, $pageSize, $loginUser['is_enterprise_user']);
        $res['list'] = $response['list'];
        $res['count'] = $response['page']['total'];

        $this->json_data = $res;
    }

}
