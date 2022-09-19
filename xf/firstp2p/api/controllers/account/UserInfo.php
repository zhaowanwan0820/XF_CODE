<?php

namespace api\controllers\account;

use api\conf\Error;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use api\controllers\FundBaseAction;
use libs\web\Form;
use libs\utils\Aes;
use core\service\UserBankcardService;

/**
 * UserInfo
 * 获取用户信息
 */
class UserInfo extends FundBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'signature' => array('filter' => "required", 'message' => '签名不能为空！'),
            'user_id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        if (!$data['user_id']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '参数校验失败');
            return false;
        }

        $user = $this->rpc->local('UserService\getUserViaSlave', array($data['user_id']));
        if ($user && $user['id'] && $user['is_delete'] == 0 && $user['is_effect'] == 1) {
            unset($user['user_pwd']);
        } else {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '无效的用户id');
            return false;
        }

        $bankInfo = array('bank_no' => '', 'bank_name' => '', 'bank_code' => '', 'is_bank_bind' => 0);
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $data['user_id']));
        $bankInfo['bank_no'] = $bankcard['bankcard'];
        if (!empty($bankcard)) {
            if (empty($bankcard['bank_id']) && $user['payment_user_id']) {
                // 获取支付系统所有银行卡列表-安全卡数据
                $obj = new UserBankcardService();
                $payBankInfo = $obj->queryBankCardsList($data['user_id'], true);
                if (!empty($payBankInfo['list'])) {
                    $bankInfo['bank_name'] = $payBankInfo['list']['bankName'];
                    $bankInfo['bank_code'] = $payBankInfo['list']['bankCode'];
                    if ($bankInfo['bank_name']) {
                        $this->rpc->local('UserBankcardService\updateBankNameByCode', array($bankcard['id'], $payBankInfo['list']['bankCode']));
                    }
                }
            } else {
                $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
                $bankInfo['bank_code'] = $bank['short_name'];
                $bankInfo['bank_name'] = $bank['name'];
            }
            $bankInfo['is_bank_bind'] = 1;
        }

        $fields = array(
            'id', 'user_name', 'real_name', 'email', 'idno', 'id_type', 'mobile',
            'idcardpassed', 'money', 'lock_money', 'address', 'phone', 'invite_code',
            'is_enterprise_user'
        );

        foreach ($fields as $field) {
            $ret[$field] = $user[$field];
        }

        // 基金需要把用户id转换为oapi的openid (有坑)
        $ret['openId'] = bin2hex(Aes::encryptForJFB($data['user_id']));

        $this->json_data = array_merge($ret, $bankInfo);
        // 记录日志
        $apiLog = $this->json_data;
        $apiLog['time'] = date('Y-m-d H:i:s');
        $apiLog['ip'] = get_real_ip();
        PaymentApi::log("API_USER_INFO_LOG:" . json_encode($apiLog), Logger::INFO);
        return true;
   }
}