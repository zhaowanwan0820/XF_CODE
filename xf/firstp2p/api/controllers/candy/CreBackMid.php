<?php

namespace api\controllers\candy;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Aes;
class CreBackMid extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'authToken' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $encodeToken = $data['authToken'];
        $aes = new Aes();
        $token = $aes->urlDecode($encodeToken);
        $decodeToken = $aes->decode($token, base64_decode($GLOBALS['sys_config']['TOKEN_ENCRYPT_KEY']));

        app_redirect("/candy/CreConvert?token=" . $decodeToken);

    }

}