<?php

namespace api\controllers\payment;

use NCFGroup\Common\Library\Idworker;
use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use core\dao\PaymentNoticeModel;
use core\dao\UserBankcardModel;
use core\dao\BankModel;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Risk;
use core\service\risk\RiskServiceFactory;
use core\service\ChargeService;
use core\service\UserBankcardService;
use core\service\SupervisionFinanceService;

class Apply extends AppBaseAction {

    const CREDIBLE_VERSION = 200;

    public function init() {
        parent::init();
        if (app_conf('MAINTENANCE_APP_PAYMENT_OFF_SWITCH') === '1') {
            $this->setErr(ERR_SYSTEM, app_conf('MAINTENANCE_APP_PAYMENT_OFF'));
            return false;
        }
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'money' => array('filter' => 'int'),
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
            'platform' => array('filter' => 'int', 'option' => array('optional' => true)),
            'os' => array('filter' => 'string', 'option' => array('optional' => true)),
            'ver' => array('filter' => 'string', 'option' => array('optional' => true)),
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

        $idinfo = $this->rpc->local('UserService\getIdnoAndType', array($loginUser['id']));
        $user_type = '00';
        if(is_array($idinfo)) {
            if ($idinfo['id_type'] == 1 && strlen($idinfo['idno']) == 18) {
                $user_type = '01';
            } elseif ($idinfo['id_type'] == 2) {
                $user_type = '04';
            } elseif ($idinfo['id_type'] == 3) {
                $user_type = '03';
            } elseif ($idinfo['id_type'] >= 4 && $idinfo['id_type'] <= 6) {
                $user_type = '02';
            } elseif ($idinfo['id_type'] == 99 ) {
                $user_type = '99';
            }
        }
        if ($user_type != '01') {
            $this->setErr('ERR_MANUAL_REASON', '手机充值只支持使用二代身份证验证的用户，如有疑问请致电' . $GLOBALS['sys_config']['SHOP_TEL']);
            return false;
        }

        if (empty($data['money'])) {
            $this->setErr('ERR_MANUAL_REASON', '请填写充值金额');
            return false;
        }

        // 银行卡唯一标识，如果没传或传默认值则表示先锋支付接口挂了
        if (empty($data['bankCardId']) || $data['bankCardId'] == UserBankcardService::BANK_CARDID_DEFAULT) {
            $this->setErr(ERR_SYSTEM, app_conf('MAINTENANCE_APP_PAYMENT_OFF'));
            return false;
        }
        $bankCardId = addslashes($data['bankCardId']);

        // 充值金额，单位分
        $moneyCent = $data['money'];
        // 转换成元
        $money = bcdiv($moneyCent, 100, 2);

        // app2.0及以上需要判断是否可信
        $isCredible = $this->rpc->local('UserCreditService\isCredible', array($loginUser['id']));
        $appVersion = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : 100;
        if ($appVersion > self::CREDIBLE_VERSION) { //app2.0
            $trustP2PFlag = $isCredible ? 1 : 0;
        } else {
            if ($isCredible !== true) {
                $this->setErr('ERR_MANUAL_REASON', '因银行系统升级维护，请您登录网信官方网站进行充值！');
                return false;
            }
        }

        $payUserExisted = $this->rpc->local("PaymentService\mobileRegister", array($loginUser['id']));
        if (!in_array($payUserExisted, array(0, 1))) { // 是否处于已经开户以及本次开户成功中
            $this->setErr('ERR_MANUAL_REASON', '创建订单失败');
            return false;
        }

        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        if ($os == 'Android') {
            $osId = PaymentNoticeModel::PLATFORM_ANDROID;
        } else {
            $osId = PaymentNoticeModel::PLATFORM_IOS;
        }

        if ($data['platform'] == PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE)
        {
            $osId = PaymentNoticeModel::PLATFORM_WEB;
        }

        $paymentId = PaymentApi::instance()->getGateway()->getConfig('common', 'PAYMENT_ID');
        $orderSn = Idworker::instance()->getId();
        // 本地创建订单
        $noticeId = $this->rpc->local('ChargeService\createOrder', array($loginUser['id'], $money, $osId, $orderSn, $paymentId));
        if (empty($noticeId)) {
            $this->setErr('ERR_SYSTEM', '创建申请订单失败！');
            return false;
        }
        $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
        $uid = $loginUser['id'];
        $amount = $money;
        $retData = [];
        // 快捷限额判断
        $needCheckLimitPlatforms = [PaymentNoticeModel::PLATFORM_ANDROID, PaymentNoticeModel::PLATFORM_IOS];
        $chargeService = new ChargeService();
        if (in_array($data['platform'], $needCheckLimitPlatforms)) {
            $limitRule = $chargeService->getLimitRuleByBankCardId($uid, $bankCardId, ChargeService::LIMIT_TYPE_APP);
            do {
                // 没有限制记录当作不限额
                if (empty($limitRule)) {
                    break;
                }
                // 只有限额记录存在并且金额不为0的时候, 判断单笔是否超限额
                if (isset($limitRule['singlelimit']) && $limitRule['singlelimit'] >= 0 && $moneyCent > $limitRule['singlelimit']) {
                    $this->setErr('ERR_MANUAL_REASON', "充值金额超过单笔限额,请重新输入充值金额");
                    return false;
                }
                // 当最小限额存在并且不为空时, 判断充值金额是否小于单笔最小限额
                if (isset($limitRule['lowlimit']) && $limitRule['lowlimit'] >= 0 && $moneyCent < $limitRule['lowlimit']) {
                    $this->setErr('ERR_MANUAL_REASON', "充值金额低于单笔最小金额,请重新输入充值金额");
                    return false;
                }
            } while (false);

            // 正常快捷充值,调用支付订单创建接口,同步充值订单数据
            $retData = $this->rpc->local('PaymentService\apply',
                array($noticeId, $merchant, $uid, $amount, $bankCardId));
            if ($retData === false || !is_array($retData)) {
                $this->setErr('ERR_SYSTEM', "充值失败。");
                return false;
            }
            // 充值成功记录日志
            $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
            $apiLog = array(
                'time' => date('Y-m-d H:i:s'),
                'userId' => $loginUser['id'],
                'ip' => get_real_ip(),
                'noticeSn' => $retData['outOrderId'],
                'noticeId' => $noticeId,
                'money' => $amount,
                'os' => $os,
                'channel' => $channel,
            );
            logger::wLog("API_PAY_APPLY:".json_encode($apiLog));
            PaymentApi::log("API_PAY_APPLY:".json_encode($apiLog), Logger::INFO);
        }

        $ret = array(
            'merchantId' => $merchant,
            'userId'     => $loginUser['id'],
            'outOrderId' => strval($orderSn),
        );
        if ($appVersion > self::CREDIBLE_VERSION) {
            $ret['trustP2PFlag'] = strval($trustP2PFlag);
        }
        $ret['sign'] = \libs\utils\Aes::signature($ret, $GLOBALS['sys_config']['XFZF_SEC_KEY']);
        RiskServiceFactory::instance(Risk::BC_CHARGE,Risk::PF_API,Risk::getDevice($_SERVER['HTTP_OS']))->check(array('id'=>$loginUser['id'],'user_name'=>$loginUser['user_name'],'money'=>$amount),Risk::ASYNC);
        $this->json_data = $ret;
        if ($data['platform'] == PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE) {
            // 设置pc检测用的redis， pc 检测成功之后清空此key
            $ret['id'] = $noticeId;
            $ret['amount'] = $amount; // 充值金额，单位元
            $ret['channel'] = 'wx'; // 充值渠道
            ChargeService::SetAppToPcChargeOrder($loginUser['id'], $ret);
        }
    }

    public function invokeOld() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $idinfo = $this->rpc->local('UserService\getIdnoAndType', array($loginUser['id']));
        $user_type = '00';
        if(is_array($idinfo)) {
            if ($idinfo['id_type'] == 1 && strlen($idinfo['idno']) == 18) {
                $user_type = '01';
            } elseif ($idinfo['id_type'] == 2) {
                $user_type = '04';
            } elseif ($idinfo['id_type'] == 3) {
                $user_type = '03';
            } elseif ($idinfo['id_type'] >= 4 && $idinfo['id_type'] <= 6) {
                $user_type = '02';
            } elseif ($idinfo['id_type'] == 99 ) {
                $user_type = '99';
            }
        }
        if ($user_type != '01') {
            $this->setErr('ERR_MANUAL_REASON', '手机充值只支持使用二代身份证验证的用户，如有疑问请致电' . $GLOBALS['sys_config']['SHOP_TEL']);
            return false;
        }

        if (empty($data['money'])) {
            $this->setErr('ERR_MANUAL_REASON', '请填写充值金额');
            return false;
        }
        // 充值金额，单位分
        $moneyCent = $data['money'];
        // 转换成元
        $money = bcdiv($moneyCent, 100, 2);

        // 新协议支付开关是否打开
        $isH5Charge = SupervisionFinanceService::isNewBankLimitOpen();
        $busType = $isH5Charge ? ChargeService::LIMIT_TYPE_NEWH5 : ChargeService::LIMIT_TYPE_APP;
        // 先锋支付充值单笔限额拦截
        $userBankCardInfo = UserBankcardModel::instance()->getNewCardByUserId($loginUser['id']);
        $bankInfo = BankModel::instance()->find($userBankCardInfo['bank_id']);
        $param = ['userId' => $loginUser['id'], 'bankCode' => $bankInfo['short_name'], 'bankCardNo' => $userBankCardInfo['bankcard'], 'busType' => $busType];
        $chargeLimitResult = $this->rpc->local('PaymentService\getNewChargeLimit', [$param]);
        if ((int)$data['platform'] !== PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE && !empty($chargeLimitResult['singleLimit'])
            && $chargeLimitResult['singleLimit'] > 0 && $chargeLimitResult['singleLimit'] < $money)
        {
            $this->setErr('ERR_MANUAL_REASON', '充值金额超过单笔限额:'.$this->rpc->local('PaymentService\formatMoney', [$chargeLimitResult['singleLimit']]).'元/笔');
            return false;
        }

        // app2.0及以上需要判断是否可信
        $isCredible = $this->rpc->local('UserCreditService\isCredible', array($loginUser['id']));
        $appVersion = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : 100;
        if ($appVersion > self::CREDIBLE_VERSION) { //app2.0
            $trustP2PFlag = $isCredible ? 1 : 0;
        } else {
            if ($isCredible !== true) {
                $this->setErr('ERR_MANUAL_REASON', '因银行系统升级维护，请您登录网信官方网站进行充值！');
                return false;
            }
        }

        $payUserExisted = $this->rpc->local("PaymentService\mobileRegister", array($loginUser['id']));
        if (!in_array($payUserExisted, array(0, 1))) { // 是否处于已经开户以及本次开户成功中
            $this->setErr('ERR_MANUAL_REASON', '创建订单失败');
            return false;
        }
        $bank_id = ''; // 此处不再记录银行
        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        if ($os == 'Android') {
            $osId = PaymentNoticeModel::PLATFORM_ANDROID;
        } else {
            $osId = PaymentNoticeModel::PLATFORM_IOS;
        }

        if ($data['platform'] == PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE)
        {
            $osId = PaymentNoticeModel::PLATFORM_WEB;
        }
        // 获取支付方式ID
        $paymentId = PaymentApi::instance()->getGateway()->getConfig('common', 'PAYMENT_ID');
        $orderSn = Idworker::instance()->getId();
        $noticeId = $this->rpc->local('ChargeService\createOrder', array($loginUser['id'], $money, $osId, $orderSn, $paymentId));
        if (empty($noticeId)) {
            $this->setErr('ERR_SYSTEM', '创建申请订单失败！');
            return false;
        }
        $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
        $uid = $loginUser['id'];
        $amount = $money;
        $cur_type = 'CNY'; // 人民币
        $notify = $GLOBALS['sys_config']['XFZF_PAY_CALLBACK'];
        $real_name = $loginUser['real_name'];
        $card_type = '01';
        $idno = $loginUser['idno'];
        $mobile = $loginUser['mobile'];
        $retData = [];
        if ((int)$data['platform'] !== PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE)
        {
            $retData = $this->rpc->local('PaymentService\apply',
                    array($noticeId, $merchant, $uid, $amount));
            if ($retData === false || !is_array($retData)) {
                $this->setErr('ERR_SYSTEM', "充值失败。");
                return false;
            }
            // 充值成功记录日志
            $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
            $apiLog = array(
                'time' => date('Y-m-d H:i:s'),
                'userId' => $loginUser['id'],
                'ip' => get_real_ip(),
                'noticeSn' => $retData['outOrderId'],
                'noticeId' => $noticeId,
                'money' => $amount,
                'os' => $os,
                'channel' => $channel,
            );
            logger::wLog("API_PAY_APPLY:".json_encode($apiLog));
            PaymentApi::log("API_PAY_APPLY:".json_encode($apiLog), Logger::INFO);
        }

        $ret = array(
            'merchantId' => $merchant,
            'userId'     => $loginUser['id'],
            'outOrderId' => strval($orderSn),
        );
        if ($appVersion > self::CREDIBLE_VERSION) {
            $ret['trustP2PFlag'] = strval($trustP2PFlag);
        }
        $ret['sign'] = \libs\utils\Aes::signature($ret, $GLOBALS['sys_config']['XFZF_SEC_KEY']);
        RiskServiceFactory::instance(Risk::BC_CHARGE,Risk::PF_API,Risk::getDevice($_SERVER['HTTP_OS']))->check(array('id'=>$loginUser['id'],'user_name'=>$loginUser['user_name'],'money'=>$amount),Risk::ASYNC);
        $this->json_data = $ret;
        if ($data['platform'] == PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE) {
            // 设置pc检测用的redis， pc 检测成功之后清空此key
            $ret['id'] = $noticeId;
            $ret['amount'] = $amount; // 充值金额，单位元
            $ret['channel'] = 'wx'; // 充值渠道
            ChargeService::SetAppToPcChargeOrder($loginUser['id'], $ret);
        }
    }
}
