<?php
/**
 * 存管系统-Web个人开户页面
 */
namespace web\controllers\supervision;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\supervision\SupervisionAccountService;
use core\service\user\BankService;

class Register extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'platform' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
        }
    }

    public function invoke() {
        try{
            $userId = $GLOBALS['user_info']['id'];
            // 获取该用户在超级账户的基本信息
            $userBaseData = $GLOBALS['user_info'];
            $params = [
                'userId' => $userBaseData['id'],
                'realName' => $userBaseData['real_name'],
                'certNo' => $userBaseData['idno'],
                'regionCode' => str_pad($userBaseData['mobile_code'], 3, 0, STR_PAD_LEFT), // 国家区域码
                'phone' => $userBaseData['mobile'],
            ];

            // 获取该用户在超级账户的绑卡信息
            $userBankCardData = BankService::getNewCardByUserId($userId);
            if (!empty($userBankCardData)) {
                $params['bankCardNo'] = $userBankCardData['bankcard'];
                $bankData = BankService::getBankInfoByBankId($userBankCardData['bank_id']);
                $params['bankCode'] = !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '';
            }

            // 调用存管系统接口
            $supervisionAccountObj = new SupervisionAccountService();
            $memberRegisterHtml = $supervisionAccountObj->memberRegister($params);

            $this->tpl->assign('register_form', $memberRegisterHtml);
            $this->tpl->assign('payment_title', '正在跳转到支付页面');
            $this->tpl->assign('payment_tip', '正在跳转到支付页面，请稍等....');
        } catch(\Exception $e) {
            return $this->show_error(sprintf('%s(%s)', $e->getMessage(), $e->getCode()));
        }
    }
}