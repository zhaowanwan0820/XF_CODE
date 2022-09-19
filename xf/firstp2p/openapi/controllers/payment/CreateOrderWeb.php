<?php

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\PaymentApi;

/**
 *
 * Class CreateOrderWeb
 * @package openapi\controllers\payment
 */
class CreateOrderWeb extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            'amount' => array('filter' => 'required', 'message' => 'amount is required'),
            "site_id" => array("filter" => "int", "message" => "site_id is error", "option" => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $amount = isset($data['amount']) ? floatval($data['amount']) : null;
        $siteId = isset($data['site_id']) ? intval($data['site_id']) : null;
        try {
            if (empty($amount)) {
                throw new \Exception('充值金额不能为空');
            }
            if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
                throw new \Exception('充值金额为小数点两位');
            }
            if (bccomp($amount, 0.00, 2) <= 0) {
                throw new \Exception('充值金额错误');
            }
            $userInfo = $this->getUserByAccessToken();
            if (!$userInfo) {
                $this->setErr('ERR_TOKEN_ERROR');
                return false;
            }
            $bankNo = $userInfo->bankNo;
            if (empty($bankNo) || $bankNo == '无') {
                throw new \Exception('没有绑定银行卡');
            }
            $chargeService = new \core\service\ChargeService();
            $orderSn = $chargeService->createOrder($userInfo->userId, $amount, \core\dao\PaymentNoticeModel::PLATFORM_WEB_THIRD, '', $siteId);
            $paymentNoticeModel = new \core\dao\PaymentNoticeModel();
            $paymentNotice = $paymentNoticeModel->find($orderSn);
            $noticeSn = $paymentNotice['notice_sn'];
            if (empty($noticeSn)) {
                throw new \Exception('创建订单失败');
            }
            $result = array();
            $result['notice_sn'] = $noticeSn;
            $result['payment_id'] = $paymentNotice['id'];
        } catch (\Exception $e) {
            $this->errorCode = -1;
            $this->errorMsg = $e->getMessage();
            PaymentApi::log('openapi CreateOrderWeb:' . $e->getMessage());
            return false;
        }
        $this->json_data = $result;
        return true;
    }

}
