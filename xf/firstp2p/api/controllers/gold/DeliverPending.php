<?php
/**
 * 提金待提交接口
 * @author xiaoan
 * @date 2017.09.20
 */


namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class DeliverPending extends GoldBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
               'token' => array(
                        'filter' => 'required',
                        'message' => 'ERR_PARAMS_VERIFY_FAIL',
                ),
                'orderId' => array(
                        'filter' => 'required',
                        'message' => 'ERR_PARAMS_VERIFY_FAIL',
                )
        );
        if (!$this->form->validate()) {
            $this->tpl->assign("error",$this->form->getErrorMsg());
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;

        $orderId = isset($data['orderId']) ? addslashes($data['orderId']) : '';
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->tpl->assign("error",'系统繁忙，请稍后重试');
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $res = $this->rpc->local('GoldService\deliverPending', array($user['id'],$orderId));

        if ($res['errCode'] != 0 || empty($res['data'])) {

            $this->tpl->assign('data', $data);
            $this->tpl->display('api/views/_v46/gold/error.html');
            return false;
        }

        header("Location:".$res['data']);
        exit;
    }



}

