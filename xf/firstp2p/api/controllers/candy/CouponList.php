<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyBucService;
use libs\web\Form;
use core\service\candy\CandyShopService;
use core\service\candy\CandyAccountService;
use core\service\AgreementService;


class CouponList extends AppBaseAction {
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

        $accountService = new CandyAccountService();
        $accountInfo = $accountService->getAccountInfo($loginUser['id']);
        $shopService = new CandyShopService();
        $couponList = $shopService->getCouponList();

        $this->tpl->assign('userSummary', $accountInfo);
        $this->tpl->assign('couponList', $couponList);
        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('');
    }
}
