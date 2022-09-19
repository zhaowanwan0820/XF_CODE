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
        if (app_conf('MAINTENANCE_APP_PAYMENT_OFF_SWITCH') === '1') {
            $this->setErr(ERR_SYSTEM, app_conf('MAINTENANCE_APP_PAYMENT_OFF'));
        }

        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        // mobileType
        $mobileType = '';
        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        if ($os == 'iOS') {
            $mobileType = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APP_IOS;
        } else {
            $mobileType = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APP_ANDROID;
        }

        // 请求来源
        $reqSource = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APPWAP;

        // 订单id
        $orderId = Idworker::instance()->getId();
        // 用户id
        $userId = $loginUser['id'];
        // 是否显示页面标题 app不需要
        $hasTitle = 'Y';

        $requestParams = [
            'userId' => $userId,
            'orderId' => $orderId,
            'reqSource' => $reqSource,
            'hasTitle' => $hasTitle,
            'mobileType' => $mobileType,
            'returnUrl' => 'storemanager://api?type=recharge',
        ];

        $requestUri = PaymentApi::instance()->getGateway()->getRequestUrl('offlineCharge',$requestParams);
        header("Location:$requestUri");
    }
}
