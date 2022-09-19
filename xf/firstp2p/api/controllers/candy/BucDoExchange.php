<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyBucService;
use libs\web\Form;

class BucDoExchange extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
            'bucAmount' => array('filter' => 'required', 'message'=> 'BUC个数不能为空'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $bucAmount = bcadd($data['bucAmount'], 0, CandyBucService::BUC_AMOUNT_DECIMALS);
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 名单校验
        if ((new \core\service\BwlistService)->inList('DEAL_CU_BLACK')) {
            $this->setErr('ERR_SYSTEM');
            return false;
        }

        $bucService = new CandyBucService();
        try {
            $bucService->exchange($loginUser['id'], $bucAmount);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->errno = -1;
        }
    }
}
