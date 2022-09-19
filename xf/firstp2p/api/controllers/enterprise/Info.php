<?php

namespace api\controllers\enterprise;

use libs\web\Form;
use api\controllers\BaseAction;

class Info extends BaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array("filter" => "required", "message" => "token is required")
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        // 获取用户信息
        $userinfo = $this->getUserByToken();
        if (!$userinfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $user = $this->rpc->local('UserService\getUserViaSlave', array($userinfo['id']));
        if ($user && $user['is_delete'] == 0 && $user['is_effect'] == 1) {
            unset($user['user_pwd']);
        } else {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '无效的用户id');
            return false;
        }

//        $bankInfo = array('bank_no' => '', 'bank_name' => '', 'bank_code' => '', 'is_bank_bind' => 0);
//        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $userinfo['id']));
//        $bankInfo['bank_no'] = $bankcard['bankcard'];
//        if (!empty($bankcard)) {
//            if (empty($bankcard['bank_id']) && $user['payment_user_id']) {
//                $payBankInfo = \libs\utils\PaymentApi::instance()->request('searchbankcards', array('userId' => $userinfo['id']));
//                if (!empty($payBankInfo)) {
//                    $bankInfo['bank_name'] = $payBankInfo['bankCards'][0]['bankName'];
//                    $bankInfo['bank_code'] = $payBankInfo['bankCards'][0]['bankCode'];
//                    if ($bankInfo['bank_name']) {
//                        $this->rpc->local('UserBankcardService\updateBankNameByCode', array($bankcard['id'], $payBankInfo['bankCards'][0]['bankCode']));
//                    }
//                }
//            } else {
//                $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
//                $bankInfo['bank_code'] = $bank['short_name'];
//                $bankInfo['bank_name'] = $bank['name'];
//            }
//            $bankInfo['is_bank_bind'] = 1;
//        }

        $fields = array(
            'id', 'user_name', 'real_name', 'email', 'idno', 'id_type', 'mobile',
            'idcardpassed', 'money', 'lock_money', 'address', 'phone', 'invite_code',
            'supervision_user_id'
        );

        $ret = array();
        $ret['member_sn'] = numTo32Enterprise($userinfo['id']);
        foreach ($fields as $field) {
            $ret[$field] = $user[$field];
        }

        $this->json_data = $ret;
    }
}