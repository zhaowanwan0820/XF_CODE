<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyActivityService;
use libs\web\Form;

class Lottery extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
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
        $activityService = new CandyActivityService();
        try {
            $result = $activityService->activityLottery($loginUser['id']);
        } catch (\Exception $e) {
            $this->errno = -1;
            $this->error = $e->getMessage();
        }
        $this->json_data = ['activity' => $result];
    }
}
