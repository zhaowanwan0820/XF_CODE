<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\bonus\BonusService;
use libs\utils\Logger;

class AjaxSend extends AppBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_GET_USER_FAIL'
            ),
            'page' => array('filter' => 'int'),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $site_id = $data['site_id'] ? $data['site_id'] : $this->defaultSiteId;
        $page = isset($data['page']) ? $data['page'] : 1;
        $pageSize = 10;
        $loginUser = $this->user;

        $result = BonusService::bonusSend($loginUser['id'], $site_id, $page, $pageSize);
        if ($result === false) {
            $this->setErr(BonusService::getErrorData(), BonusService::getErrorMsg());
            return false;
        }

        $this->json_data = $result;
    }

}
