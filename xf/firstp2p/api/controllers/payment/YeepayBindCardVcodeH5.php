<?php

/**
 * 易宝-绑卡-短信验证码页面-APP
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\YeepayBaseAction;
use core\service\UserBankcardService;
use api\conf\ConstDefine;

/**
 * 易宝-绑卡-短信验证码页面-APP
 * 
 */
class YeepayBindCardVcodeH5 extends YeepayBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'bankName' => array('filter' => 'string'),
            'bankCard' => array('filter' => 'string'),
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
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

    public function invokeOld($data, $userInfo) {
        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];
        if (!empty($data['bankName']) && !empty($data['bankCard']))
        {
            // 银行名称
            $bankName = addslashes($data['bankName']);
            // 银行卡号
            $bankCard = addslashes($data['bankCard']);
        }else{
            $userBankCardService = new UserBankcardService();
            $bankCardInfo = $userBankCardService->getBankcard($userId);
            if (!empty($bankCardInfo))
            {
                // 银行卡号
                $bankCard = !empty($bankCardInfo['bankcard']) ? $bankCardInfo['bankcard'] : '';
                // 银行名称
                $bankService = new \core\service\BankService();
                $bankInfo = $bankService->getBank($bankCardInfo['bank_id']);
                $bankName = !empty($bankInfo['name']) ? $bankInfo['name'] : '';
                unset($userBankCardService, $bankService);
            }
        }
        // 用户注册手机号
        $userPhone = strlen($userInfo['mobile']) > 0 ? $userInfo['mobile'] : '';

        // 临时Token
        $this->tpl->assign('asgn', $this->setAsgnToken());
        // 载入绑卡请求后，手机验证码页面
        $this->tpl->assign('bankName', $bankName);
        $this->tpl->assign('bankCard', $bankCard);
        $this->tpl->assign('phone', $userPhone);
        $this->tpl->assign('userClientKey', $userClientKey);
        $this->template = $this->getTemplate('yeepay_bind_card_vcode_h5');
        return true;
    }

    public function invokeNew($data, $userInfo) {
        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];

        // 用户注册手机号
        $userPhone = strlen($userInfo['mobile']) > 0 ? $userInfo['mobile'] : '';

        if (!empty($data['bankName']) && !empty($data['bankCard']))
        {
            // 银行名称
            $bankName = addslashes($data['bankName']);
            // 银行卡号
            $bankCard = addslashes($data['bankCard']);
            // 取用户支付卡列表中的指定bankcardid的卡数据
            $bankcardServ = new UserBankcardService();
            $bankCardInfo = $bankcardServ->queryBankCardsList($userId, false, $data['bankCardId']);
            $bankCardInfo = isset($bankCardInfo['list']) ? $bankCardInfo['list'] : [];
            // 银行卡预留手机号
            $userPhone = !empty($bankCardInfo['phone']) ? $bankCardInfo['phone'] : $userPhone;
        } else {
            $userBankCardService = new UserBankcardService();
            $bankCardInfo = $userBankCardService->getBankcard($userId);
            if (!empty($bankCardInfo))
            {
                // 银行卡号
                $bankCard = !empty($bankCardInfo['bankcard']) ? $bankCardInfo['bankcard'] : '';
                // 银行名称
                $bankService = new \core\service\BankService();
                $bankInfo = $bankService->getBank($bankCardInfo['bank_id']);
                $bankName = !empty($bankInfo['name']) ? $bankInfo['name'] : '';
                unset($userBankCardService, $bankService);
            }
        }

        // 临时Token
        $this->tpl->assign('asgn', $this->setAsgnToken());
        // 载入绑卡请求后，手机验证码页面
        $this->tpl->assign('bankName', $bankName);
        $this->tpl->assign('bankCard', $bankCard);
        $this->tpl->assign('bankCardId', $data['bankCardId']);
        $this->tpl->assign('phone', $userPhone);
        $this->tpl->assign('userClientKey', $userClientKey);
        $this->tpl->assign('appVersion', $data['appVersion']);
        $this->template = $this->getTemplate('yeepay_bind_card_vcode_h5');
        return true;
    }
}
