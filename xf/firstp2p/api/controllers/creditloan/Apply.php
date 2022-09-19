<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

/**
 * Apply
 * 申请借款
 *
 * @uses BaseAction
 * @package default
 */
class Apply extends AppBaseAction
{
    const UNIQUE_ID_KEY = 'HpyZ@UPiTEx#gN36IMJBnju'; // 用于加密id

    const IS_H5 = true;

    public function init()
    {

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
           'deal_id' => array(
               'filter' => 'required',
               'message' => 'ERR_PARAMS_VERIFY_FAIL',
           ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user_info = $this->getUserByToken();

        // get deal info
        $credit_deal = $this->rpc->local('CreditLoanService\getCreditDealInfoForUser', array($data['deal_id'], $user_info['id']));
        $bank_list = $GLOBALS['dict']['CREDITLOAN_BANKLIST'];

        $isFreePaymentYxt = 1;
        if ($credit_deal['report_status'] == 1) {
            $isFreePaymentYxt = $this->rpc->local('SupervisionService\isFreePayment', array($user_info['id'], 2));
        }
        // assign
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('unique_id', md5($user_info['id'] . self::UNIQUE_ID_KEY));
        $this->tpl->assign('credit_deal', $credit_deal);
        $this->tpl->assign('bank_list', $bank_list);
        $this->tpl->assign('isFreePaymentYxt', intval($isFreePaymentYxt));
    }
}
