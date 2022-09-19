<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use core\service\o2o\DiscountService;

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
        $loginUser = $this->user;

        $count = DiscountService::getMineUnusedDiscountCount($loginUser['id']);
        if ($count === false) {
            $count = array('all'=>0, 'p2p'=>0, 'gold'=>0, 'used' => 0);
        }

        $this->json_data = $count;
    }
}
