<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\bonus\BonusService;

class GetUnused extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_GET_USER_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $loginUser = $this->user;
        $unusedBonusCount = 0; //普惠不显示红包组信息，为后续扩展所以保留代码逻辑
        $result['summary'] = $unusedBonusCount . "组";

        if ($unusedBonusCount < 1) {
            $userInfo = BonusService::getUsableBonus($loginUser['id'], false, 0, false, $loginUser['id']);

            $result['summary'] = $userInfo['money'] . app_conf('NEW_BONUS_UNIT');
        }
        $this->json_data = $result;
        return;
    }

}

