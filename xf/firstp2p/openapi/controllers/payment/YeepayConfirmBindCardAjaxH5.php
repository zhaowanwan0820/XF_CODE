<?php

/**
 * 易宝-绑卡确认Ajax接口-H5
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\YeepayBaseAction;
use libs\utils\PaymentApi;
use core\service\YeepayPaymentService;

/**
 * 易宝-绑卡确认Ajax接口-H5
 * 
 */
class YeepayConfirmBindCardAjaxH5 extends YeepayBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'vcode' => array('filter' => 'required', 'message' => 'vcode is required'),
            'asgn' => array('filter' => 'required', 'message' => 'asgn is required'),
            'requestid' => array('filter' => 'string'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
        $asgn = $this->getAsgnToken();
        if ($asgn !== $this->form->data['asgn']) {
            $this->setErr('ERR_SYSTEM_ACTION_PERMISSION');
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        // 检查用户是否已在先锋支付开户
        if ($userInfo->paymentUserId <= 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '您尚未开户无法进行充值，请稍后再试');
            return false;
        }

        $data = $this->form->data;
        if (empty($data['vcode']) || !is_numeric($data['vcode']) || strlen($data['vcode']) != 6)
        {
            $this->setErr('ERR_MANUAL_REASON', '验证码不能为空或格式不正确');
            return false;
        }

        // 用户ID
        $userId = $userInfo->userId;
        // 用户身份校验Key
        $userClientKey = isset($data['userClientKey']) ? $data['userClientKey'] : '';
        // 调用“4.1.2 确认绑卡接口”
        $yeepayPaymentService = new YeepayPaymentService();
        $confirmBindCardRet = $yeepayPaymentService->confirmBindBankCard($userId, $data['vcode']);
        // 如果返回该卡已绑定，则允许进入确认充值页面(TZ1001028:已绑卡成功)
        if (!isset($confirmBindCardRet['respCode']) || ($confirmBindCardRet['respCode'] !== '00' && $confirmBindCardRet['respCode'] !== 'TZ1001028'))
        {
            $this->setErr('ERR_MANUAL_REASON', $confirmBindCardRet['respMsg']);
            return false;
        }
        if (!isset($confirmBindCardRet['data']['requestno']) || empty($confirmBindCardRet['data']['requestno']))
        {
            // 绑卡成功后，获取redis中的卡号、银行名称等信息
            $userOrderInfo = $this->getUserRedisOrderInfo();
            $requestId = isset($userOrderInfo['requestId']) ? $userOrderInfo['requestId'] : '';
        }else{
            $requestId = $confirmBindCardRet['data']['requestno'];
            // 更新银行名称，存到redis哨兵
            $redis = YeepayPaymentService::getRedisSentinels();
            if ($redis)
            {
                // 银行简码
                $bankCode = isset($confirmBindCardRet['data']['bankcode']) ? $confirmBindCardRet['data']['bankcode'] : '';
                $cacheData = array(
                    'bankCode' => $bankCode,
                    'cardTop' => $confirmBindCardRet['data']['cardtop'],
                    'cardLast' => $confirmBindCardRet['data']['cardlast'],
                    'requestId' => $requestId,
                );
                // 易宝支持的16家银行
                $quickBankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
                if (isset($quickBankList[$bankCode]) && !empty($quickBankList[$bankCode]))
                {
                    $cacheData['bankName'] = $quickBankList[$bankCode];
                }
                $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_ORDER_API, $userClientKey);
                $redis->hMset($cacheKey, $cacheData);
            }
        }
        $this->json_data = array('code'=>1, 'url'=>sprintf('/payment/yeepayConfirmBindCardH5?rid=%s&userClientKey=%s', $requestId, $userClientKey));
        return true;
    }
}