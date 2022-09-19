<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\cre\Cre;
use libs\utils\Aes;
use libs\web\Form;

class CreMid extends AppBaseAction
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
        $backUrl = $this->getHost() . "/candy/CreBackMid?authToken=" . $encodeToken;

        // 获取用户ID
        $aes = new Aes();
        $aesToken = $aes->urlDecode($encodeToken);
        $token = $aes->decode($aesToken, base64_decode($GLOBALS['sys_config']['TOKEN_ENCRYPT_KEY']));
        $loginUser = $this->getUserByToken(true, $token);
        $userId = $loginUser['id'];

        app_redirect(cre::instance()->getCreRegisterUrl($userId, $backUrl));
    }

}
