<?php

/**
 * 易宝-绑卡Ajax接口-APP
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\YeepayBaseAction;
use core\service\YeepayPaymentService;
use core\service\UserBankcardService;

/**
 * 易宝-绑卡Ajax接口-APP
 * 
 */
class YeepayBindCardAjaxH5 extends YeepayBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'phone' => array('filter' => 'required', 'message' => 'phone is required'),
            'asgn' => array('filter' => 'required', 'message' => 'asgn is required'),
            'bankName' => array('filter' => 'string'),
            'bankCard' => array('filter' => 'string'),
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate())
        {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
        $asgn = $this->getAsgnToken();
        if ($asgn !== $this->form->data['asgn'])
        {
            $this->setErr('ERR_PARAMS_ERROR', '页面已失效，请刷新后重试');
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo))
        {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        // 检查用户是否已在先锋支付开户
        if ($userInfo['payment_user_id'] <= 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '您尚未开户无法进行充值，请稍后再试');
            return false;
        }

        $data = $this->form->data;
        if (empty($data['phone']) || !is_numeric($data['phone']) || strlen($data['phone']) != 11 || !is_mobile($data['phone']))
        {
            $this->setErr('ERR_PARAMS_ERROR', '手机号不能为空或格式不正确');
            return false;
        }

        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];
        // 获取redis中的信息
        $userOrderInfo = $this->getUserRedisOrderInfo();
        // isAccord(0:易宝不支持的银行 1:易宝支持的银行)
        if (isset($userOrderInfo['isAccord']) && $userOrderInfo['isAccord'] == 1)
        {
            $userBankCardService = new UserBankcardService();
            if (!empty($data['bankCardId'])) {
                // 取用户支付卡列表中的指定bankcardid的卡数据
                $bankCardInfo = $userBankCardService->queryBankCardsList($userId, false, $data['bankCardId']);
                if (empty($bankCardInfo['list'])) {
                    $this->setErr('ERR_MANUAL_REASON', '用户指定的银行卡信息无效,请返回充值界面重新选择银行卡');
                    return false;
                }
                // 提取卡信息
                $bankCardInfo = $bankCardInfo['list'];
                $bankName = !empty($bankCardInfo['bankName']) ? $bankCardInfo['bankName'] : '';
                $bankCard = !empty($bankCardInfo['cardNo']) ? $bankCardInfo['cardNo'] : '';
            } else {
                // 银行卡号
                $bankCardData = $userBankCardService->getBankcard($userId);
                if (empty($bankCardData) || empty($bankCardData['bankcard']))
                {
                    $this->setErr('ERR_PARAMS_ERROR', '要绑定的银行卡号不存在');
                    return false;
                }
                $bankCard = $bankCardData['bankcard'];
                // 银行名称
                $bankService = new \core\service\BankService();
                $bankData = $bankService->getBank($bankCardData['bank_id']);
                if (empty($bankData) || empty($bankData['name']))
                {
                    $this->setErr('ERR_PARAMS_ERROR', '要绑定的银行名称不存在');
                    return false;
                }
                $bankName = $bankData['name'];
            }
        }else{
            // 银行名称
            if (!isset($data['bankName']) || empty($data['bankName']))
            {
                $this->setErr('ERR_PARAMS_ERROR', '银行名称不能为空');
                return false;
            }
            // 银行卡号
            if (!isset($data['bankCard']) || empty($data['bankCard']))
            {
                $this->setErr('ERR_PARAMS_ERROR', '银行卡号不能为空');
                return false;
            }
            $bankName = addslashes($data['bankName']);
            $bankCard = addslashes($data['bankCard']);
        }
        $params = array();
        // 当前登录用户ID
        $params['uid'] = $userId;
        // 银行卡号
        $params['cardno'] = $bankCard;
        // 证件号码
        $params['idcardno'] = $userInfo['idno'];
        // 真实姓名
        $params['username'] = $userInfo['real_name'];
        // 银行预留手机号
        $params['phone'] = addslashes($data['phone']);

        // 调用“4.1.1 绑卡请求接口”
        $yeepayPaymentService = new YeepayPaymentService();
        $bindCardRet = $yeepayPaymentService->bindBankCard($params);
        if (!isset($bindCardRet['respCode']) || $bindCardRet['respCode'] !== '00')
        {
            $this->setErr('ERR_PARAMS_ERROR', isset($bindCardRet['respMsg']) ? $bindCardRet['respMsg'] : '绑定银行卡失败，请重试');
            return false;
        }

        // 把用户的银行名称、卡号，存到redis哨兵
        $redis = YeepayPaymentService::getRedisSentinels();
        if ($redis)
        {
            $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
            $cacheData = array(
                'bankName' => $bankName,
                'cardTop' => substr($bankCard, 0, 6),
                'cardLast' => substr($bankCard, -4),
                'requestId' => !empty($bindCardRet['data']['requestno']) ? $bindCardRet['data']['requestno'] : '',
            );
            $redis->hMset($cacheKey, $cacheData);
        }
        $this->json_data = array('code'=>1);
        return true;
    }
}
