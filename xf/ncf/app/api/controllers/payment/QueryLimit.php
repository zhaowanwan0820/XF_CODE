<?php
/**
 * 网贷P2P账户充值，获取充值限额
 */
namespace api\controllers\payment;

use libs\web\Form;
use libs\utils\Logger;
use api\controllers\AppBaseAction;
use core\service\user\BankService;
use core\service\payment\PaymentUserAccountService;

class QueryLimit extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => '登录错误，请重新登录'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        $result = ['limitDesc'=>''];
        $limitDesc = [];

        try {
            $bankInfo = BankService::getNewCardByUserId($loginUser['id'], 'bank_id');
            if (empty($bankInfo['bank_id'])) {
                throw new \Exception('银行卡信息不存在');
            }

            $paymentUserAccountObj = new PaymentUserAccountService();
            $limitRes = $paymentUserAccountObj->getChargeLimit(intval($bankInfo['bank_id']));
            if (!isset($limitRes['respCode']) || $limitRes['respCode'] != '00') {
                $errorMsg = !empty($limitRes['respMsg']) ? $limitRes['respMsg'] : '获取充值限额失败';
                Logger::error(sprintf('userId:%d, bankId:%d, limitRes:%s, GetBankLimitServiceError:%s', $loginUser['id'], $bankInfo['bank_id'], json_encode($limitRes), $errorMsg));
                throw new \Exception($errorMsg);
            }

            // 单笔最大限额
            $single = round(intval($limitRes['data']['singleLimit']) / 10000, 2, PHP_ROUND_HALF_DOWN);
            if ($single > 0) {
                $limitDesc[] = $single . '万/笔';
            }
            // 日最大限额
            $day = round(intval($limitRes['data']['dayLimit']) / 10000, 2, PHP_ROUND_HALF_DOWN);
            if ($day > 0) {
                $limitDesc[] = $day . '万/日';
            }
            // 月最大限额
            $month = round(intval($limitRes['data']['monthLimit']) / 10000, 2, PHP_ROUND_HALF_DOWN);
            if ($month > 0) {
                $limitDesc[] = $month . '万/月';
            }
            if (!empty($limitDesc)) {
                $result['limitDesc'] = '充值限额，' . join('，', $limitDesc);
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('userId:%d, GetBankLimitApiException:%s', $loginUser['id'], $e->getMessage()));
        }
        $this->json_data = $result;
    }
}