<?php
/**
 * 点击“充值按钮”的接口-新接口（APP）
 *
 * @author <weiwei12@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\PaymentService;
use core\service\PaymentUserAccountService;

/**
 *
 * 点击充值按钮检查限额
 * @package api\controllers\payment
 */
class PreCharge extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'money' => array('filter' => 'required'), //充值金额 单位元
            'platform' => array('filter' => 'required'), //1网贷  2网信
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
        $userId = $loginUser['id'];
        $platform = (int) $data['platform'];
        $money = (float) $data['money'];

        $paymentService = new PaymentService();
        $accountServ = new PaymentUserAccountService();

        $result = $paymentService->preCharge($loginUser, $accountServ, $money, $platform);
        if (!empty($result['errno'])) {
            $this->setErr('ERR_MANUAL_REASON', $result['error']);
        }
        $this->json_data = $result['data'];
        return true;
    }
}
