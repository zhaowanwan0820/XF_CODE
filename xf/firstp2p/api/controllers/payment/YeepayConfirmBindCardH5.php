<?php

/**
 * 易宝-绑卡确认页面-APP
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\YeepayBaseAction;
use libs\utils\PaymentApi;
use core\service\YeepayPaymentService;

/**
 * 易宝-绑卡确认页面-APP
 * 
 */
class YeepayConfirmBindCardH5 extends YeepayBaseAction {

    const IS_H5 = true;

    protected $useSession = true;
    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'rid' => array('filter' => 'required', 'message' => 'rid is required'),
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
        if (empty($data['rid']) || !is_numeric($data['rid']))
        {
            $this->setErr('ERR_MANUAL_REASON', '绑卡请求号不能为空或格式不正确');
            return false;
        }
        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];
        // 获取绑卡请求号并校验
        $requestId = \es_session::get(sprintf('%s%d', YeepayPaymentService::KEY_YEEPAY_BINDCARD, $userId));
        if (empty($requestId) || strcmp($data['rid'], $requestId) !== 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '绑卡请求号校验失败');
            return false;
        }

        // 绑卡成功后，获取redis中的充值订单号、充值金额等
        $userOrderInfo = $this->getUserRedisOrderInfo();
        // 银行名称
        $bankNameCache = isset($userOrderInfo['bankName']) ? $userOrderInfo['bankName'] : '';
        // 银行编码
        $bankCode = isset($userOrderInfo['bankCode']) ? $userOrderInfo['bankCode'] : '';
        // 银行名称为空时的处理
        if (empty($bankNameCache) && !empty($bankCode))
        {
            // 易宝支持的16家银行列表
            $quickBankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
            if (isset($quickBankList[$bankCode]) && !empty($quickBankList[$bankCode]))
            {
                $bankName = $quickBankList[$bankCode];
            }
        }else{
            $bankName = $bankNameCache;
        }
        // 银行卡前6位
        $cardTop = isset($userOrderInfo['cardTop']) ? $userOrderInfo['cardTop'] : '';
        // 银行卡后4位
        $cardLast = isset($userOrderInfo['cardLast']) ? $userOrderInfo['cardLast'] : '';
        // 生成脱敏卡号
        $bankCard = YeepayPaymentService::getFormatBankCard($cardTop, $cardLast);
        // 充值金额，单位元
        $amountYuan = (isset($userOrderInfo['amountFen']) && !empty($userOrderInfo['amountFen'])) ? bcdiv($userOrderInfo['amountFen'], 100, 2) : 0;
        // 充值成功后，跳转的页面
        $returnUrl = !empty($userOrderInfo['returnUrl']) ? $userOrderInfo['returnUrl'] : $this->getAppScheme('native', array('name'=>'mine'));
        // 充值报错后，跳转的页面
        $returnLoginUrl = !empty($userOrderInfo['returnLoginUrl']) ? $userOrderInfo['returnLoginUrl'] : $this->getAppScheme('native', array('name'=>'login'));

        // 临时Token
        $this->tpl->assign('asgn', $this->setAsgnToken());
        // 绑卡成功后，确认支付页面
        $this->tpl->assign('amount', $amountYuan); // 充值金额
        $this->tpl->assign('bankName', $bankName); // 银行名称
        $this->tpl->assign('bankCard', $bankCard); // 银行卡号
        $this->tpl->assign('returnUrl', $returnUrl);
        $this->tpl->assign('returnLoginUrl', $returnLoginUrl);
        $this->tpl->assign('userClientKey', $userClientKey);
        $this->template = $this->getTemplate('yeepay_confirm_bind_card_h5');
        return true;
    }
}
