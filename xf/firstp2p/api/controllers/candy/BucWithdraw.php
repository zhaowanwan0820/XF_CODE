<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyBucService;
use libs\web\Form;
use core\service\candy\CandyAccountService;

class BucWithdraw extends AppBaseAction {
    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->form = new Form('get');
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

        // 名单校验
        if ((new \core\service\BwlistService)->inList('DEAL_CU_BLACK')) {
            $this->setErr('ERR_SYSTEM');
            return false;
        }

        $bucService = new CandyBucService();
        $accountService = new CandyAccountService();

        $lastWithdrawAddress = $bucService->getLastWithdrawAddress($loginUser['id']);
        $accountInfo = $accountService->getAccountInfo($loginUser['id']);

        $this->tpl->assign('bucAmount', $accountInfo['buc_amount']);
        $this->tpl->assign('lastWithdrawAddress', $lastWithdrawAddress);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('platform', $this->getOs());
        $this->tpl->assign('mobile', $loginUser['mobile']);
    }
}
