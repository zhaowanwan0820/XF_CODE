<?php
namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\PaymentApi;
use core\service\QrCodeService;
use NCFGroup\Protos\Ptp\Enum\PaymentConfigEnum;
use api\conf\ConstDefine;
use core\service\PaymentService;

// 网信-电脑端网银扫一扫页面
class OfflineCharge extends AppBaseAction {
    const IS_H5 = true;
    const CREDIBLE_VERSION = 200;
    // 20190115即将上线的版本号
    const OFFCHARGE_VERSION = 41000;

    public function init() {
        parent::init();
        if (app_conf('MAINTENANCE_APP_PAYMENT_OFF_SWITCH') === '1') {
            $this->setErr(ERR_SYSTEM, app_conf('MAINTENANCE_APP_PAYMENT_OFF'));
            return false;
        }
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'ref' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
    }

    public function invoke() {
        $version = $this->getAppVersion();
        if ($version >= ConstDefine::VERSION_MULTI_CARD) {
            return $this->invokeNew();
        }
        return $this->invokeOld();
    }

    public function invokeNew() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // mobileType
        $mobileType = '';
        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        if ($os == 'iOS') {
            $mobileType = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APP_IOS;
        } else {
            $mobileType = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APP_ANDROID;
        }

        // 入口来源，区分是否已经创建了充值订单（快捷超限额、大额充值）
        $chargeRef = !empty($data['ref']) ? $data['ref'] : '';
        $isSaveOrder = $chargeRef === QrCodeService::QRREF_QUICK ? 1 : 0;

        // 请求来源
        $reqSource = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APPWAP;

        // 用户id
        $userId = $loginUser['id'];

        // 记录日志
        PaymentApi::log(sprintf('%s, userId：%s, params：%s, 进入大额充值或扫一扫页面', __METHOD__, $userId, json_encode($data)));

        $appVersion = $this->getAppVersion();
        // 请前往PC端完成网银充值，进入扫一扫提示页面
        if ($appVersion >= self::OFFCHARGE_VERSION) {
            $this->tpl->assign('isSaveOrder', $isSaveOrder);
            $this->template = $this->getTemplate('no_support_large_charge');
        } else {
            // 您绑定的银行卡暂不支持大额充值
            $this->template = $this->getTemplate('no_support_large_charge');
        }
        return true;
    }

    public function invokeOld() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // mobileType
        $mobileType = '';
        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        if ($os == 'iOS') {
            $mobileType = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APP_IOS;
        } else {
            $mobileType = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APP_ANDROID;
        }

        // 入口来源，区分是否已经创建了充值订单（快捷超限额、大额充值）
        $chargeRef = !empty($data['ref']) ? $data['ref'] : '';
        $isSaveOrder = $chargeRef === QrCodeService::QRREF_QUICK ? 1 : 0;

        // 请求来源
        $reqSource = PaymentConfigEnum::UCFPAY_REQUEST_SOURCE_APPWAP;
        // 用户id
        $userId = $loginUser['id'];

        // 检查用户绑定的银行是否在大额充值银行的白名单里
        $largeAmountOpen = PaymentService::isOfflineBankList($userId);
        // 用户在黑名单
        $inBlackList = PaymentService::inBlackList($userId);

        // 记录日志
        PaymentApi::log(sprintf('%s, userId：%s, params：%s, offChargeSwitch：%d，isOfflineBankList：%d，isBlackList：%d，进入大额充值或扫一扫页面', __METHOD__, $userId, json_encode($data), $largeAmountOpen, $inBlackList));

        // 支持大额充值并且不在黑名单，才能进入大额充值页面
        if ($largeAmountOpen && !$inBlackList) {
            app_redirect(sprintf('/payment/offlineChargeOrder?token=%s&ver=%d', $data['token'], $this->getAppVersion()));
            exit;
        }

        $appVersion = $this->getAppVersion();
        // 请前往PC端完成网银充值，进入扫一扫提示页面
        if ($appVersion >= self::OFFCHARGE_VERSION) {
            $this->tpl->assign('isSaveOrder', $isSaveOrder);
            $this->template = $this->getTemplate('no_support_large_charge');
        } else {
            // 您绑定的银行卡暂不支持大额充值
            $this->template = $this->getTemplate('no_support_large_charge');
        }
        return true;
    }
}