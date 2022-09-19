<?php

namespace web\controllers\help;

use libs\web\Form;
use web\controllers\BaseAction;

class UserAgreement extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "site_id" => array("filter" => "int", "message" => "site_id is error"),
                "adv" => array("filter" => "string", 'option' => array('optional' => true)),
                "title" => array("filter" => "string", 'option' => array('optional' => true)),
                );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $adv = !empty($data['adv']) ? $data['adv'] : (is_qiye_site() ? 'regist_protocol':'qy_regist_protocol');
        $title = !empty($data['title']) ? $data['title'] : '注册协议';
        $this->tpl->assign("adv", $adv);
        $this->tpl->assign("title", $title);
        $this->template = 'web/views/help/user_agreement.html';
    }

}
