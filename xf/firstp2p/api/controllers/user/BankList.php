<?php
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

// 获取银行支行信息
class BankList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'p'=>array("filter"=>'required', 'message' => 'ERR_PARAMS_ERROR'),//省份
            'c'=>array("filter"=>'required', 'message' => 'ERR_PARAMS_ERROR'),//城市
            'b'=>array("filter"=>'required', 'message' => 'ERR_PARAMS_ERROR'),//银行名称
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

        $bankList = $this->rpc->local('BanklistService\getBanklist',array($data['c'], $data['p'], $data['b']));
        $this->json_data = !empty($bankList) ? array('list' => $bankList) : ['list'=>[]];
        return true;
    }
}