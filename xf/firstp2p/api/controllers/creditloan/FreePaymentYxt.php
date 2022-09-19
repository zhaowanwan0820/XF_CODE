<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

/**
 * 银信通免密提现协议 
 * @author longbo
 */
class FreePaymentYxt extends AppBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user_info = $this->getUserByToken();
        if (empty($user_info)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return $this->return_error();
        }
        $isFreePaymentYxt = $this->rpc->local('SupervisionService\isFreePayment', array($user_info['id'], 2));
        // assign
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('isFreePaymentYxt', intval($isFreePaymentYxt));
    }
}
