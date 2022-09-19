<?php

/**
 * 多投宝赎回成功页
 * @author 王传路 <wangchuanlu@ucfgroup.com>
 */
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

class FinplanSuccess extends BaseAction {

    public function init() {
        $this->check_login();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            app_redirect(url("index"));
        }
    }

    public function invoke() {
        $loan_id = $this->form->data['id'];

        $this->template = "web/views/account/finplan_success.html";
    }
}
