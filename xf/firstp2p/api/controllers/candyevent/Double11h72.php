<?php

namespace api\controllers\candyevent;

use api\controllers\AppBaseAction;
use libs\web\Form;

class Double11h72 extends AppBaseAction
{
    const IS_H5 = true;
    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data; 
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('shopUrl', $GLOBALS['sys_config']['LIFE_SHOP']['SHOP_HOST']);
        $this->template = $this->getTemplate('');
    }

}
