<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use libs\utils\Monitor;

/**
 * 网信速贷 用户服务协议
 *
 * @uses BaseAction
 * @package default
 */
class UserServiceAgreement extends SpeedLoanBaseAction
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
        $this->tpl->assign('mortageRate', app_conf('SPEED_LOAN_SERVICE_RATE'));
        $this->template = 'speedloan/user_services_agreement.html';
        return;
    }
}
