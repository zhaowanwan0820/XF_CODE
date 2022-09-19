<?php

/**
 * 易宝-绑卡页面/充值页面-页面-APP
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use api\controllers\YeepayBaseAction;
use libs\web\Form;
use libs\utils\PaymentApi;
use core\service\YeepayPaymentService;
use core\service\UserBankcardService;
use api\conf\ConstDefine;

/**
 * 易宝-绑卡页面/充值页面-APP
 *
 */
class YeepayStartPayH5 extends YeepayBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'money' => array('filter' => 'float', 'option' => array('optional' => true)),
            // 兼容旧版本, 选填
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
            // appVersion, 易宝丢了appVersion
            'appVersion' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate())
        {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $this->getAppScheme('native', array('name'=>'login')));
            return false;
        }
        // 检查用户是否已在先锋支付开户
        if ($userInfo['payment_user_id'] <= 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '您尚未开户无法进行充值，请稍后再试');
            return false;
        }

        $data = $this->form->data;
        if (!empty($data['appVersion']) && $data['appVersion'] >= ConstDefine::VERSION_MULTI_CARD) {
            return $this->invokeNew($data, $userInfo);
        }

        return $this->invokeOld($data, $userInfo);
    }

    /**
     * 兼容指定版本以下的易宝充值逻辑
     */
    public function invokeOld($data, $userInfo) {
        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];
        // 获取绑卡信息列表
        $yeepayPaymentService = new YeepayPaymentService();
        $cardBindList = $yeepayPaymentService->bankCardAuthBindList($userId);
        if (!isset($cardBindList['respCode']) || $cardBindList['respCode'] !== '00')
        {
            $this->setErr('ERR_MANUAL_REASON', $cardBindList['respMsg']);
            return false;
        }

        // 取用户先锋支付绑卡数据
        $userBankCardService = new UserBankcardService();
        $bankCardInfo = $userBankCardService->getBankcard($userId);
        // 检查用户绑卡是否是最新的卡
        $checkResult = $yeepayPaymentService->checkCardIsExists($bankCardInfo['bankcard'], $cardBindList['data']['cardlist']);
        // 用户尚未绑卡或者先锋支付银行卡不是易宝绑定卡的任何一张,进行绑卡处理
        if (!isset($cardBindList['data']['cardlist']) || empty($cardBindList['data']['cardlist']) || $checkResult === false)
        {
            $bankName = $bankCard = $bankCode = '';
            if (!empty($bankCardInfo) && !empty($bankCardInfo['bankcard']))
            {
                $cardTop = substr($bankCardInfo['bankcard'], 0, 6);
                $cardLast = substr($bankCardInfo['bankcard'], -4);
                // 银行卡号
                $bankCard = YeepayPaymentService::getFormatBankCard($cardTop, $cardLast);
                // 银行名称
                $bankService = new \core\service\BankService();
                $bankInfo = $bankService->getBank($bankCardInfo['bank_id']);
                $bankName = !empty($bankInfo['name']) ? $bankInfo['name'] : '';
                $bankCode = !empty($bankInfo['short_name']) ? $bankInfo['short_name'] : '';
                unset($userBankCardService, $bankService);
            }

            // 需要先验卡再充值
            $quickBankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
            if (YeepayPaymentService::isInBankListByCode($bankCode) && !empty($bankCard))
            {
                // 先锋支付绑的卡，易宝支持
                $isAccord = 1;
            }else{
                $bankName = $bankCard = '';
                // 先锋支付绑的卡，易宝不支持
                $isAccord = 0;
            }
            // 用户银行卡是否在易宝支付的银行列表中存入redis，存到redis哨兵
            $redis = YeepayPaymentService::getRedisSentinels();
            if ($redis)
            {
                $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
                $redis->hSet($cacheKey, 'isAccord', $isAccord);
            }
            // 易宝支持的16家银行列表
            $bankSelectList = !empty($quickBankList) ? array_values($quickBankList) : array();
            $this->tpl->assign('isAccord', $isAccord);
            $this->tpl->assign('realName', $userInfo['real_name']);
            $this->tpl->assign('idno', idnoFormat($userInfo['idno']));
            $this->tpl->assign('bankName', $bankName);
            $this->tpl->assign('bankCard', $bankCard);
            $this->tpl->assign('bankList', json_encode($bankSelectList));
            $this->tpl->assign('userClientKey', $userClientKey);
            // 载入验卡页面
            $this->template = $this->getTemplate('yeepay_bindcard_h5');
        } else {
            // 获取用户缓存中的订单信息
            $userOrderInfo = $this->getUserRedisOrderInfo();
            // 用户在易宝的绑卡信息，载入充值页面(获取与用户当前卡匹配的银行卡)
            $cardListOne = $checkResult;
            // 充值金额，单位元
            $amountYuan = (isset($userOrderInfo['amountFen']) && !empty($userOrderInfo['amountFen'])) ? bcdiv($userOrderInfo['amountFen'], 100, 2) : 0;
            // 生成脱敏卡号
            $bankCard = YeepayPaymentService::getFormatBankCard($cardListOne['cardtop'], $cardListOne['cardlast']);
            // 银行名称
            $bankName = $yeepayPaymentService->getBankNameByCode($cardListOne['bankcode']);
            $redis = YeepayPaymentService::getRedisSentinels();
            if ($redis)
            {
                $cacheData = array(
                    'cardTop' => $cardListOne['cardtop'],
                    'cardLast' => $cardListOne['cardlast'],
                    'bankName' => $bankName,
                );
                $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
                $redis->hMset($cacheKey, $cacheData);
            }

            // 临时Token
            $this->tpl->assign('asgn', $this->setAsgnToken());
            // 易宝-确认充值页面
            $this->tpl->assign('amount', $amountYuan);
            $this->tpl->assign('bankName', $bankName);
            $this->tpl->assign('bankCard', $bankCard);
            $this->tpl->assign('returnUrl', !empty($userOrderInfo['returnUrl']) ? $userOrderInfo['returnUrl'] : $this->getAppScheme('native', array('name'=>'mine')));
            $this->tpl->assign('returnLoginUrl', !empty($userOrderInfo['returnLoginUrl']) ? $userOrderInfo['returnLoginUrl'] : $this->getAppScheme('native', array('name'=>'login')));
            $this->tpl->assign('userClientKey', $userClientKey);
            // 载入充值页面
            $this->template = $this->getTemplate('yeepay_start_pay_h5');
        }
        return true;
    }


    public function invokeNew($data, $userInfo) {
        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];
        // 获取绑卡信息列表
        $yeepayPaymentService = new YeepayPaymentService();
        $cardBindList = $yeepayPaymentService->bankCardAuthBindList($userId);
        if (!isset($cardBindList['respCode']) || $cardBindList['respCode'] !== '00')
        {
            $this->setErr('ERR_MANUAL_REASON', $cardBindList['respMsg']);
            return false;
        }

        // 取用户支付卡列表中的指定bankcardid的卡数据
        $bankcardServ = new UserBankcardService();
        $bankCardInfo = $bankcardServ->queryBankCardsList($userId, false, $data['bankCardId']);
        if (empty($bankCardInfo['list'])) {
            $this->setErr('ERR_MANUAL_REASON', '用户指定的银行卡信息无效,请返回充值界面重新选择银行卡');
            return false;
        }
        // 提取卡信息
        $bankCardInfo = $bankCardInfo['list'];

        // 检查用户绑卡是否是最新的卡
        $checkResult = $yeepayPaymentService->checkCardIsExists($bankCardInfo['cardNo'], $cardBindList['data']['cardlist']);
        // 用户尚未绑卡或者先锋支付银行卡不是易宝绑定卡的任何一张,进行绑卡处理
        if (!isset($cardBindList['data']['cardlist']) || empty($cardBindList['data']['cardlist']) || $checkResult === false)
        {
            $bankName = $bankCard = $bankCode = '';
            if (!empty($bankCardInfo) && !empty($bankCardInfo['cardNo']))
            {
                $cardTop = substr($bankCardInfo['cardNo'], 0, 6);
                $cardLast = substr($bankCardInfo['cardNo'], -4);
                // 银行卡号
                $bankCard = YeepayPaymentService::getFormatBankCard($cardTop, $cardLast);
                // 银行名称
                $bankName = !empty($bankCardInfo['bankName']) ? $bankCardInfo['bankName'] : '';
                $bankCode = !empty($bankCardInfo['bankCode']) ? $bankCardInfo['bankCode'] : '';
            }

            // 需要先验卡再充值
            $quickBankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
            if (YeepayPaymentService::isInBankListByCode($bankCode) && !empty($bankCard))
            {
                // 先锋支付绑的卡，易宝支持
                $isAccord = 1;
            }else{
                $bankName = $bankCard = '';
                // 先锋支付绑的卡，易宝不支持
                $isAccord = 0;
            }
            // 用户银行卡是否在易宝支付的银行列表中存入redis，存到redis哨兵
            $redis = YeepayPaymentService::getRedisSentinels();
            if ($redis)
            {
                $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
                $redis->hSet($cacheKey, 'isAccord', $isAccord);
            }
            // 易宝支持的16家银行列表
            $bankSelectList = !empty($quickBankList) ? array_values($quickBankList) : array();
            $this->tpl->assign('isAccord', $isAccord);
            $this->tpl->assign('realName', $userInfo['real_name']);
            $this->tpl->assign('idno', idnoFormat($userInfo['idno']));
            $this->tpl->assign('bankName', $bankName);
            $this->tpl->assign('bankCard', $bankCard);
            $this->tpl->assign('bankCardId', $data['bankCardId']);
            $this->tpl->assign('bankList', json_encode($bankSelectList));
            $this->tpl->assign('userClientKey', $userClientKey);
            $this->tpl->assign('appVersion', $data['appVersion']);
            // 载入验卡页面
            $this->template = $this->getTemplate('yeepay_bindcard_h5');
        } else {
            // 获取用户缓存中的订单信息
            $userOrderInfo = $this->getUserRedisOrderInfo();
            // 用户在易宝的绑卡信息，载入充值页面(获取与用户当前卡匹配的银行卡)
            $cardListOne = $checkResult;
            // 充值金额，单位元
            $amountYuan = (isset($userOrderInfo['amountFen']) && !empty($userOrderInfo['amountFen'])) ? bcdiv($userOrderInfo['amountFen'], 100, 2) : 0;
            // 生成脱敏卡号
            $bankCard = YeepayPaymentService::getFormatBankCard($cardListOne['cardtop'], $cardListOne['cardlast']);
            // 银行名称
            $bankName = $yeepayPaymentService->getBankNameByCode($cardListOne['bankcode']);
            $redis = YeepayPaymentService::getRedisSentinels();
            if ($redis)
            {
                $cacheData = array(
                    'cardTop' => $cardListOne['cardtop'],
                    'cardLast' => $cardListOne['cardlast'],
                    'bankName' => $bankName,
                );
                $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
                $redis->hMset($cacheKey, $cacheData);
            }

            // 临时Token
            $this->tpl->assign('asgn', $this->setAsgnToken());
            // 易宝-确认充值页面
            $this->tpl->assign('amount', $amountYuan);
            $this->tpl->assign('bankName', $bankName);
            $this->tpl->assign('bankCard', $bankCard);
            $this->tpl->assign('bankCardId', $data['bankCardId']);
            $this->tpl->assign('returnUrl', !empty($userOrderInfo['returnUrl']) ? $userOrderInfo['returnUrl'] : $this->getAppScheme('native', array('name'=>'mine')));
            $this->tpl->assign('returnLoginUrl', !empty($userOrderInfo['returnLoginUrl']) ? $userOrderInfo['returnLoginUrl'] : $this->getAppScheme('native', array('name'=>'login')));
            $this->tpl->assign('userClientKey', $userClientKey);
            $this->tpl->assign('appVersion', $data['appVersion']);
            // 载入充值页面
            $this->template = $this->getTemplate('yeepay_start_pay_h5');
        }
        return true;
    }
}
