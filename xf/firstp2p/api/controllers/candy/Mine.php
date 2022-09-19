<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyProduceService;
use libs\web\Form;
use core\service\candy\CandyAccountService;
use core\service\candy\CandyBucService;

class Mine extends AppBaseAction {
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
        $produceService = new CandyProduceService();

        $userSummary = $accountService->getAccountInfo($loginUser['id']);
        $amount = empty($userSummary) ? 0 : $userSummary['amount'];
        $bucService = new CandyBucService();

        //信宝黑名单
        $showConfig['BLACK_BUC'] = (new \core\service\BwlistService)->inList('DEAL_CU_BLACK');
        $this->tpl->assign('showConfig', $showConfig);

        $userSummary['amount'] = number_format($amount, 3);
        $userSummary["candyCashValue"] = number_format($accountService->calcCandyWorth($amount), 2);
        $userSummary['bucAmount'] = number_format($userSummary['buc_amount'], 6);
        $this->tpl->assign('userSummary', $userSummary);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('isBucOpen', $bucService->isOpen());

        $this->tpl->assign('isProduceDone', $produceService->isBatchProduceDone(date('Ymd', strtotime("-1 days"))));
        $this->template = $this->getTemplate('');
    }
}
