<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyAccountService;
use core\service\candy\CandyBucService;
use core\service\AgreementService;

class BucExchange extends AppBaseAction {
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

        //用户授权检查
        $this->tpl->assign('token', $data['token']);
        if (!AgreementService::check($loginUser['id'], 'candy')) {
            $this->template = 'api/views/_v48/candy/shop_agreement.html';
            return false;
        }

        $accountService = new CandyAccountService();
        $bucService = new CandyBucService();
        $accountInfo = $accountService->getAccountInfo($loginUser['id']);

        $bucExchageLimit = $bucService->getBucLimit();
        $bucUserLimit = $bucService->getUserExchangeBucUsed($loginUser['id']);

        $this->tpl->assign('bucAmount', number_format($bucService->calcBucAmount($accountInfo['amount']), 3));
        if (empty($accountInfo['amount'])) {
            $accountInfo['amount'] = 0;
        } else {
            $accountInfo['amount'] = number_format($accountInfo['amount'], 3);
        }

        $this->tpl->assign('accountInfo', $accountInfo);
        $this->tpl->assign('isShowTotalLimit', intval($bucExchageLimit['buc_amount_total']) != CandyBucService::EXCHANGE_BUC_DAILY_TOTAL_LIMIT_DEFAULT);
        $this->tpl->assign('isShowUserLimit', intval($bucExchageLimit['buc_amount_user_total']) != CandyBucService::EXCHANGE_BUC_DAILY_USER_LIMIT_DEFAULT);
        $this->tpl->assign('bucExchageTotalLimit', number_format(bcsub($bucExchageLimit['buc_amount_total'], $bucExchageLimit['buc_amount_used'], CandyBucService::BUC_AMOUNT_DECIMALS), 6));
        $this->tpl->assign('bucExchageUserLimit', number_format(bcsub($bucExchageLimit['buc_amount_user_total'], $bucUserLimit, CandyBucService::BUC_AMOUNT_DECIMALS), 6));
    }
}
