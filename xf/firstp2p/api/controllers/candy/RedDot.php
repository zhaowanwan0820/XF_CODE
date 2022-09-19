<?php
namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyAccountService;

class RedDot extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!empty($redis) && $redis->get(CandyAccountService::RED_DOT_KEY_PREFIX. $loginUser['id'])) {
            $showRedDot = false;
        } else {
            $accountService = new CandyAccountService();
            $showRedDot = $accountService->hasCandyUpdate($loginUser['id'], date('Ymd')) ? true : false;
        }

        $this->json_data = ['showRedDot' => $showRedDot];
    }
}
