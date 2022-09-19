<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\BaseAction;

class AjaxUnpickList extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
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

        $page = intval($data['page']);
        $page = $page ? $page : 1;

        $rpcParams = array($loginUser['id'], $page);
        $unPickList = $this->rpc->local('O2OService\getUnpickList', $rpcParams);

        $this->json_data = $unPickList;
    }
}
