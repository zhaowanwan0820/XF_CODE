<?php
namespace api\controllers\common;
/**
 * 投篮验证
 * @author longbo
 */

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use libs\utils\Logger;

class ShootVerify extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'sessionid' => array('filter' => 'required', 'message' => 'sessionid is required'),
                );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $sessionid = trim($data['sessionid']);
        $from = $this->getOs() == 1 ? 'ios' : 'android';
        $verifyRs = $this->rpc->local("UserVerifyService\shootVerify", array($sessionid, $from));
        if (!$verifyRs) {
            $this->setErr('ERR_SHOOT_VERIFY_FAIL');
        }
        $this->json_data = ['verifyToken' => $verifyRs];

    }
}

