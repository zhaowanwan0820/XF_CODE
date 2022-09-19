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

class Apply extends AppBaseAction {

    public function init() {
        parent::init();
        if (app_conf('MAINTENANCE_APP_PAYMENT_OFF_SWITCH') === '1') {
            $this->setErr(ERR_SYSTEM, app_conf('MAINTENANCE_APP_PAYMENT_OFF'));
        }

        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'money' => array('filter' => 'int'),
            'os' => array('filter' => 'string', 'option' => array('optional' => true)),
            'ver' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

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
            $this->setErr('ERR_MANUAL_REASON', '手机充值只支持使用二代身份证验证的用户，如有疑问请致电95782');
        }

        if (empty($data['money'])) {
            $this->setErr('ERR_MANUAL_REASON', '请填写充值金额');
        }

        // 充值金额，单位分
        $moneyCent = $data['money'];
        // 转换成元
        $money = bcdiv($moneyCent, 100, 2);
        // 限制最少金额
        if($moneyCent < 100)
        {
            //$this->setErr('ERR_PARAMS_VERIFY_FAIL', '充值金额最低1元');
            //return false;
        }

        // app2.0及以上需要判断是否可信
        $isCredible = $this->rpc->local('UserCreditService\isCredible', array($loginUser['id']));
        $trustP2PFlag = $isCredible ? 1 : 0;
        $payUserExisted = $this->rpc->local("PaymentService\mobileRegister", array($loginUser['id']));
        if (!in_array($payUserExisted, array(0, 1))) { // 是否处于已经开户以及本次开户成功中
            $this->setErr('ERR_MANUAL_REASON', '创建订单失败');
        }

        $bank_id = ''; // 此处不再记录银行
        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        if ($os == 'Android') {
            $osId = PaymentNoticeModel::PLATFORM_ANDROID;
        } else {
            $osId = PaymentNoticeModel::PLATFORM_IOS;
        }
        // 获取支付方式ID
        $paymentId = PaymentApi::instance()->getGateway()->getConfig('common', 'PAYMENT_ID');
        $noticeId = $this->rpc->local('ChargeService\createOrder', array($loginUser['id'], $money, $osId, '', $paymentId));
        if (!empty($noticeId)) {
            $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
            $uid = $loginUser['id'];
            $amount = $money;
            $cur_type = 'CNY'; // 人民币
            $notify = $GLOBALS['sys_config']['XFZF_PAY_CALLBACK'];
            $real_name = $loginUser['real_name'];
            $card_type = '01';
            $idno = $loginUser['idno'];
            $mobile = $loginUser['mobile'];
            $retData = $this->rpc->local('PaymentService\apply',
                        array($noticeId, $merchant, $uid, $amount, $cur_type, $real_name, $card_type, $idno, $mobile));
            if ($retData === false || !is_array($retData)) {
                $this->setErr('ERR_SYSTEM', "充值失败。");
            } else {
                // 充值成功记录日志
                $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
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

                $ret = array(
                    'merchantId' => $retData['merchantId'],
                    'userId' => $retData['userId'],
                    'outOrderId' => $retData['outOrderId'],
                );

                $ret['trustP2PFlag'] = strval($trustP2PFlag);
                $ret['sign'] = \libs\utils\Aes::signature($ret, $GLOBALS['sys_config']['XFZF_SEC_KEY']);
                $this->json_data = $ret;
                RiskServiceFactory::instance(Risk::BC_CHARGE,Risk::PF_API,Risk::getDevice($_SERVER['HTTP_OS']))->check(array('id'=>$loginUser['id'],'user_name'=>$loginUser['user_name'],'money'=>$amount),Risk::ASYNC);
                return true;
            }
        } else {
            $this->setErr('ERR_SYSTEM', '创建申请订单失败！');
        }
    }
}
