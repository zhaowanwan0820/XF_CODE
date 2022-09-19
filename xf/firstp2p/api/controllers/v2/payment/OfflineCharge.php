<?php

namespace api\controllers\payment;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use core\dao\PaymentNoticeModel;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\Ptp\Enum\PaymentConfigEnum;
// 大额充值入口
class OfflineCharge extends AppBaseAction {

    const CREDIBLE_VERSION = 200;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'return_url' => array('filter' => 'required', 'message' => 'return_url is required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
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

        // mobileType
        $mobileType = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_WAP_IOS;

        // 请求来源
        $reqSource = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APPWAP;

        // 订单id
        $orderId = Idworker::instance()->getId();
        // 用户id
        $userId = $loginUser['id'];
        // 是否显示页面标题 app不需要
        $hasTitle = 'Y';
        // 回调地址
        $returnUrl = !empty($data['return_url']) ? $data['return_url'] : 'storemanager://api?type=recharge';

        $requestParams = [
            'userId' => $userId,
            'orderId' => $orderId,
            'reqSource' => $reqSource,
            'hasTitle' => $hasTitle,
            'mobileType' => $mobileType,
            'returnUrl' => $returnUrl,
        ];

        $form = PaymentApi::instance()->getGateway()->getForm('offlineCharge',$requestParams,'offlineChargeForm',false);
        $result = [
            'status' => 1,
            'form' => $form,
            'formId' => 'offlineChargeForm',
        ];
        $this->json_data = $result;
    }
}
