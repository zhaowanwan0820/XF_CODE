<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

/**
 * 用户未使用投资券个数汇总
 */
class AjaxMineCount extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
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

        $rpcParams = array($loginUser['id']);
        $count = $this->rpc->local('O2OService\getMineUnusedDiscountCount', $rpcParams);
        if ($count === false) {
            $count = array('all'=>0, 'p2p'=>0, 'gold'=>0, 'used' => 0);
        }

        $this->json_data = $count;
    }
}
