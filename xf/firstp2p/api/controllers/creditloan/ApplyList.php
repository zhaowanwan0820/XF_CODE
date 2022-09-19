<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\ABControl;

/**
 * ApplyList
 * 借款列表页
 *
 * @uses BaseAction
 * @package default
 */
class ApplyList extends AppBaseAction
{

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required','message' => 'ERR_PARAMS_VERIFY_FAIL',),
            "offset" => array("filter" => "int", "message" => "offset is error", 'option' => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", 'option' => array('optional' => true)),
            "ajax" => array("filter" => "int", "message" => "count is error", 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        $offset = empty($params['offset']) ? 0 : intval($params['offset']);
        $count = empty($params['count']) ? 20 : intval($params['count']);
        $ajax = empty($params['ajax']) ? 0 : intval($params['ajax']);

        $user_info = $this->getUserByToken();
        $credit_loan_record_list = $this->rpc->local('CreditLoanService\getCreditLoanRecordListByUserId', array($user_info['id'], $offset, $count));

        if($ajax > 0) {
            $this->json_data = $credit_loan_record_list;
        } else {
            // assign
            $this->tpl->assign('isWhiteList', ABControl::getInstance()->hit('speedLoan') ? 1 : 0);
            $this->tpl->assign('is_h5', true);
            $this->tpl->assign('credit_loan_record_list', $credit_loan_record_list);
            $this->tpl->assign('token', $params['token']);
        }
    }
}
