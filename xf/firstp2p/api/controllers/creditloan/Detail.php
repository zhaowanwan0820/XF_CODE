<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

/**
 * Detail
 * 借款详情
 *
 * @uses BaseAction
 * @package default
 */
class Detail extends AppBaseAction
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
           'credit_loan_id' => array(
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
        $credit_loan_record = $this->rpc->local('CreditLoanService\getCreditLoanRecordByCreditLoanId', array($data['credit_loan_id']));

        // assign
        $this->tpl->assign('credit_loan_record', $credit_loan_record);
    }
}
