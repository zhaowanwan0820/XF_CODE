<?php

/**
 * 内容与广告公共入口
 * @author 悟空<sunxuefeng@ucfgroup.com>
 */

namespace web\controllers\adv;

use web\controllers\BaseAction;
use libs\web\Form;

class Info extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'adv_name' => array('filter' => 'required', '广告名字不能为空'),
        );
        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 1, 1);
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $advName = addslashes($data['adv_name']);

        $this->tpl->assign('adv_name', $advName);
    }
}
