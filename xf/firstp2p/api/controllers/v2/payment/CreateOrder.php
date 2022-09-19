<?php

/**
 * @abstract api 创建支付订单
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\PaymentGatewayApi;
use libs\utils\PaymentApi;
use core\dao\PaymentNoticeModel;
use core\service\YeepayPaymentService;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;

class CreateOrder extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token"        => array("filter" => "required", "message" => "token is required"),
            'amount'       => array('filter' => 'required', 'message' => 'amount is required'),
            'return_url'    => array('filter' => 'required', 'message' => 'return_url is required'),
            'ptype'        => array('filter' => 'string', 'option' => array('optional' => true)),
            'show_nav'     => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        // 充值金额，单位元
        $amount = isset($data['amount']) ? floatval($data['amount']) : null;
        try {
            if (empty($amount)) {
                throw new \Exception('充值金额不能为空');
            }
            if (!preg_match('/^\d+(\.\d{1,2})?$/',$amount)){
                throw new \Exception('充值金额为小数点两位');
            }
            if(bccomp($amount,0.00,2) <= 0){
                throw new \Exception('充值金额错误');
            }

            // 支付方式
            $pType = isset($data['ptype']) && !empty($data['ptype']) ? addslashes($data['ptype']) : PaymentApi::PAYMENT_SERVICE_UCFPAY;
            // 获取当前可用的支付方式
            $paymentChannelList = PaymentApi::getPaymentChannel();
            // 没有可用的支付方式
            if (empty($paymentChannelList))
            {
                throw new \Exception('暂无可用的支付渠道');
            }
            // 先锋支付降级时，不能提供充值服务
            if ($pType === PaymentApi::PAYMENT_SERVICE_UCFPAY && PaymentApi::isServiceDown())
            {
                throw new \Exception(PaymentApi::maintainMessage());
            }
            // 易宝支付关闭时，不能提供充值服务
//             if ($pType === PaymentApi::PAYMENT_SERVICE_YEEPAY && (!isset($paymentChannelList[$pType]) || empty($paymentChannelList[$pType])))
//             {
//                 $yeepayCloseTips = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'CREATE_ORDER_TIPS');
//                 $yeepayCloseTips = !empty($yeepayCloseTips) ? $yeepayCloseTips : PaymentApi::maintainMessage();
//                 throw new \Exception($yeepayCloseTips);
//             }

            // 根据token，获取用户信息
            $userInfo = $this->getUserByToken();
            if (!$userInfo) {
                $this->setErr('ERR_TOKEN_ERROR');
                return false;
            }
            // 检查用户是否已在先锋支付开户
            if ($userInfo['payment_user_id'] <= 0)
            {
                throw new \Exception('您尚未开户无法进行充值，请稍后再试');
            }

            // 用户ID
            $userId = $userInfo['id'];
            $formData = array();
            // 充值金额，单位分
            $amountFen = bcmul($amount, 100, 0);
            switch ($pType)
            {
                case PaymentApi::PAYMENT_SERVICE_UCFPAY: // 先锋支付
                    // 后台已配置为开启状态
                    if (isset($paymentChannelList[$pType]) && !empty($paymentChannelList[$pType]))
                    {   
                        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $userId));
                        $bankId = $bankcard['bank_id'];
                        if (empty($bankId) || $bankId == '无') {
                            throw new \Exception('没有绑定银行卡');
                        }

                        // 获取支付方式ID
                        $paymentId = PaymentApi::instance()->getGateway()->getConfig('common', 'PAYMENT_ID');

                        $chargeService = new \core\service\ChargeService();
                        $orderSn = $chargeService->createOrder($userId, $amount, PaymentNoticeModel::PLATFORM_H5, '', $paymentId);
                        $paymentNoticeModel = new PaymentNoticeModel();
                        $paymentNotice = $paymentNoticeModel->find($orderSn);
                        $noticeSn = $paymentNotice['notice_sn'];
                        if (empty($noticeSn)) {
                            throw new \Exception('创建订单失败');
                        }

                        // 生成[先锋支付]的form表单
                        $formData['outOrderId'] = $noticeSn;
                        $formData['userId'] = $userId;
                        $formData['amount'] = $amountFen;
                        $formData['returnUrl'] = $data['return_url'];
                        $formData['changeBankCardUrl'] = $this->getChangeBankCardUrl($data);
                        if (!empty($data['show_nav'])) {
                            $formData['showNav'] = 1;
                        }
                        $form = PaymentGatewayApi::instance()->getForm('h5charge', $formData, 'h5chargeForm', false);
                    }else{
                        $createOrderTips = PaymentApi::instance()->getGateway()->getConfig('common', 'CREATE_ORDER_TIPS');
                        $createOrderExceptionTips = !empty($createOrderTips) ? $createOrderTips : PaymentApi::maintainMessage();
                        throw new \Exception($createOrderExceptionTips);
                    }
                    break;
                case PaymentApi::PAYMENT_SERVICE_YEEPAY: // 易宝支付
                    // 后台已配置为开启状态
                    if (isset($paymentChannelList[$pType]) && !empty($paymentChannelList[$pType]))
                    {
                        // 获取支付方式ID
                        $paymentId = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'PAYMENT_ID');

                        $chargeService = new \core\service\ChargeService();
                        $orderSn = $chargeService->createOrder($userId, $amount, PaymentNoticeModel::PLATFORM_H5, '', $paymentId);
                        $paymentNoticeModel = new PaymentNoticeModel();
                        $paymentNotice = $paymentNoticeModel->find($orderSn);
                        $noticeSn = $paymentNotice['notice_sn'];
                        if (empty($noticeSn)) {
                            throw new \Exception('创建订单失败');
                        }

                        $redis = YeepayPaymentService::getRedisSentinels();
                        if (!$redis)
                        {
                            throw new \Exception('暂无可用的存储服务，请稍后再试');
                        }
                        // 把用户信息、订单信息，存到redis哨兵，有效期30分钟
                        $userClientKey = md5(sprintf('%s|%s|%s', $data['client_id'], $data['token'], $noticeSn));
                        $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
                        $cacheData = array(
                            'userId' => $userId,
                            'orderId' => $noticeSn,
                            'amountFen' => $amountFen,
                            'returnUrl' => $data['return_url'],
                            'client_id' => $data['client_id'],
                            'token' => $data['token'],
                        );
                        $redis->hMset($cacheKey, $cacheData);
                        $redis->expire($cacheKey, 3600);

                        // 生成[易宝支付]的form表单
                        $formData['userClientKey'] = $userClientKey;
                        $form = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getForm('payBindRequest', $formData, 'h5chargeForm', false, $this->clientConf['client_secret']);
                    }else{
                        $createOrderTips = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'CREATE_ORDER_TIPS');
                        $createOrderExceptionTips = !empty($createOrderTips) ? $createOrderTips : PaymentApi::maintainMessage();
                        throw new \Exception($createOrderExceptionTips);
                    }
                    break;
                default:
                    throw new \Exception('不支持的支付方式');
                    break;
            }
            $result['status'] = 1;
            $result['formId'] = 'h5chargeForm';
            $result['form'] = $form;
        } catch (\Exception $e) {
            $this->errorCode = -1;
            $this->errorMsg = $e->getMessage();
            PaymentApi::log('CreateOrder:'.$e->getMessage());
            return false;
        }
        RiskServiceFactory::instance(Risk::BC_CHARGE,Risk::PF_OPEN_API,$this->device)->check(array('id'=>$userInfo->userId,'user_name'=>$userInfo->userName,'money'=>$amount),Risk::ASYNC);
        $this->json_data = $result;
        return true;
    }

    /**
    * 换卡页面回跳设置
    * @para data  $this->form->data
    */
    private function getChangeBankCardUrl($data){
        $clientId = $data['client_id'];
        $urlConf = $GLOBALS['sys_config']['CHANGE_BANKCARD_URL_MAP'];
        if( isset($urlConf[$clientId]) && !empty($urlConf[$clientId]) ){
            return urlencode($urlConf[$clientId]);
        }
        return '';
    }
}
