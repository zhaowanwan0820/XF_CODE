<?php
/**
 * 降龙活动页面
 *
 * @date 2014-10-11
 */

namespace web\controllers\event;

use libs\web\Form;
use web\controllers\BaseAction;

class Xianglong extends BaseAction {

    public function init() {

        $this->form = new Form();
        $this->form->rules = array(
                'cn' => array('filter' => 'string'),
        );  
        $this->form->validate();
    }   

    public function invoke() {
        app_redirect(url("/"));
        exit;
        $data = $this->form->data;
        $cn = trim($data['cn']);
        $this->tpl->assign('cn', $cn);
    }

}
