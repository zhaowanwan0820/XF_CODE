<?php

namespace api\controllers\payment;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;

class Check extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        //$this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => '登录错误，请重新登录'),
            'order_id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$loginUser = $this->rpc->local('UserService\getUser', array(102));

        $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
        $business_type = ConstDefine::XFZF_BUSINESS_TYPE;
        $retData = $this->rpc->local("PaymentService\check", array($data['order_id'], $merchant, $business_type));
        if ($retData === false) {
            $this->setErr(0, '查询失败！');
            return false;
        } elseif ($retData['status'] == '00') {
            //&& $retData['orderStatus'] == '00') {
            $ret = array(
                        'order_id' => $retData['outOrderId'],
                        'payer_id' => $retData['payerId'],
                        'payee_id' => $retData['receiverId'],
                        'amount' => $retData['amount'],
                        'cur_type' => $retData['curType'],
                        'business_type' => $retData['businessType'],
                        'order_status' => $retData['orderStatus'],
                        'gmt_finished' => date('Y-m-d H:i:s', strtotime($retData['gmt_finished'])),
                    );
            $ret['signature'] = \libs\utils\Aes::signature($ret, $GLOBALS['sys_config']['XFZF_SEC_KEY']);
            $this->json_data = $ret;
            return true;
        } else {
            $this->setErr(0, '订单未成功状态');
            return false;
        }
    }
}
