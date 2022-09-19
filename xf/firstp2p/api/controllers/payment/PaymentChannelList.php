<?php
/**
 * 获取当前可用的支付方式列表-接口-APP
 *
 * @author 郭峰<guofeng3@ucfgroup.com>
 */
namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\PaymentUserAccountService;

/**
 *
 * 获取当前可用的支付方式列表
 * @package api\controllers\payment
 */
class PaymentChannelList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'os' => array('filter' => 'string', 'option' => array('optional' => true)),
            'ver' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate())
        {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser))
        {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 获取该用户可用的充值渠道
        $paymentChannelList = PaymentUserAccountService::getAvailableChargeChannel($loginUser['id']);
        $this->json_data = array('paymentList'=>$paymentChannelList['list']);
        return true;
    }
}
