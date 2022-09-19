<?php
/**
 * 提金维护页面
 */
namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use core\service\GoldDeliverService;

class Error extends GoldBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'token is required'),
                'orderId' => array('filter' => 'required', 'message' => 'orderId is required'),
                );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }

    }
    public function invoke() {
        $data = $this->form->data;
        $this->tpl->assign('data', $data);
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }
        $res = $this->rpc->local('GoldService\getDeliverInfoByOrderId', array($data['orderId']));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON','获取订单信息失败');
            return false;
        }
        if(empty($res)){
            $this->setErr('订单不存在');
            return false;
        }
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }
}

