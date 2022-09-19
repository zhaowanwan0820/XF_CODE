<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

/**
 * Index
 * 可申请借款页
 *
 * @uses BaseAction
 * @package default
 */
class Index extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
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
        $params = $this->form->data;
        $user_info = $this->getUserByToken();
        $credit_loan_record_list = $this->rpc->local('CreditLoanService\getCreditLoanRecordListByUserId', array($user_info['id'], 0, 20));
        $this->assign('credit_loan_record_list', $credit_loan_record_list);
        $this->assign('is_h5', true);
        $this->assign('token', $params['token']);

        $this->template = 'creditloan/apply_list.html';
        return true;

        // get deal list
        $credit_deal_list = $this->rpc->local('CreditLoanService\getCreditDealsByUserId', array($user_info['id']));
        $is_exist_record = $this->rpc->local('CreditLoanService\getCreditLoanCountByUserId', array($user_info['id'])) ? true : false;

        // assign
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('credit_deal_list', $credit_deal_list);
        $this->tpl->assign('is_exist_record', $is_exist_record);
    }
}
