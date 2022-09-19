<?php
/**
 * 网信房贷 借款记录
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.28
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class LoanList extends AppBaseAction {

    // const IS_H5 = true;
    const PAGE_SIZE = 10;        // 分页，每页的页数

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true))
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
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
        $loanList = $this->rpc->local('HouseService\getLoanList', array($loginUser['id'], $data['page'], self::PAGE_SIZE), 'house');
        $this->json_data = $loanList;
    }
}
