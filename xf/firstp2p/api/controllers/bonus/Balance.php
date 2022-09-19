<?php
namespace api\controllers\bonus;

use api\controllers\BonusBaseAction;
use libs\web\Form;

/**
 * Balance
 * 用户红包余额查询
 */
class Balance extends BonusBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'userId' => array('filter' => 'int', 'message' => '用户id不合法'),
        );
        $this->form->rules = array_merge($this->generalFormRules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $sumMoney = $this->rpc->local('BonusService\getUserSumMoney', array(array('userId' => $data['userId'], 'status' => 1)));
        $this->json_data = array('userId' => $data['userId'], 'total' => round(floatval($sumMoney) * 100));
    }
}
