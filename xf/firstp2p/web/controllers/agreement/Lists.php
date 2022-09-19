<?php
/**
 * 协议列表
 */

namespace web\controllers\agreement;

use libs\web\Form;
use web\controllers\BaseAction;

class Lists extends BaseAction
{

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'advid' => array('filter' => 'required', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            return ajax_return(array());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $this->tpl->assign('data',$data);
        $this->template = 'web/views/agreement/lists.html';
    }

}
