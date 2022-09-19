<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use libs\utils\Monitor;

/**
 * 网信速贷 授信借款协议
 *
 * @uses BaseAction
 * @package default
 */
class MortageAgreement extends SpeedLoanBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $this->tpl->assign('userInfo', $userInfo);
        $this->template = 'speedloan/mortage_agreement.html';
        return;
    }
}
