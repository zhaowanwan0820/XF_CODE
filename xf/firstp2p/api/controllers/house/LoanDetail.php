<?php
/**
 * 网信房贷 借款记录详情页
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.28
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Ptp\Enum\HouseEnum;

class LoanDetail extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'order_id' => array('filter' => 'string', 'option' => array('optional' => true))
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $order_id = isset($data['order_id']) ? intval($data['order_id']) : '';
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $res = $this->rpc->local('HouseService\getLoanDetail', array($order_id), 'house');
        $res['actual_money'] = number_format($res['actual_money'], 2, ".", ",");

        $this->tpl->assign('borrow_log_detail', $res);
        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('loan_record_details');
    }
}
