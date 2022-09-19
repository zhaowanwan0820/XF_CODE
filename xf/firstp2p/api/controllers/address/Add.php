<?php
namespace api\controllers\address;

/**
 * User address delete
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Add extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'returnUrl' => array('filter' => 'string', 'option' => ['optional' => true]),
            'entryType' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if (!($user = $this->getUserByToken())) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $this->tpl->assign('entryType',$data['entryType']);
        $this->tpl->assign('token', $data['token']);
        $returnUrl = empty($data['returnUrl']) ? '' : $data['returnUrl'];
        $this->tpl->assign('returnUrl', $returnUrl);
    }
}
