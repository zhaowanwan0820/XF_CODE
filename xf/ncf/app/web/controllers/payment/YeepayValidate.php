<?php
/**
 * 易宝个人中心充值操作- 在易宝设置充值卡
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use libs\web\Form;
use libs\web\Url;
use web\controllers\BaseAction;
use core\service\user\UserBindService;

class YeepayValidate extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
            'bankName' => ['filter' => 'required', 'msg' => '请选择银行卡所属银行'],
            'cardNo' => ['filter' => 'required', 'msg' => '请填写银行卡信息'],
        );
        $this->form->validate();
    }

    public function invoke() {
        $userId = $GLOBALS['user_info']['id'];
        // 收集用户相关数据，通过session读取
        $sessionData = \es_session::get('yeepay_order_'.$userId);
        if (empty($sessionData['mobile']))
        {
            app_redirect(Url::gene('account', 'charge'));
        }
        // 用户如果没有在支付开户
        $checkBindCardRet = UserBindService::isBindBankCard($userId);
        if (false == $checkBindCardRet['ret'] && $checkBindCardRet['respCode'] == UserBindService::STATUS_BINDCARD_PAYMENTUSERID)
        {
            return $this->show_error('充值渠道升级中，请稍后再试', '', 0, 0, '/account');
        }

        //如果未绑定手机
        if (false == $checkBindCardRet['ret'] && ($checkBindCardRet['respCode'] == UserBindService::STATUS_BINDCARD_IDCARD || $checkBindCardRet['respCode'] == UserBindService::STATUS_BINDCARD_MOBILE))
        {
            return $this->show_error('请先填写身份证信息', '', 0, 0, $addbankUrl);
        }

        $data = $this->form->data;
        // 如果是表单提交的银行卡
        if (isset($sessionData['lockFields']) && false === $sessionData['lockFields'])
        {
            if (!empty($data['cardNo']))
            {
                $sessionData['cardNo'] = $data['cardNo'];
            }
            if (!empty($data['bankName']))
            {
                $sessionData['bankName'] = $data['bankName'];
            }
        }
        \es_session::set('yeepay_order_'.$userId, $sessionData);
        $this->tpl->assign('userInfo', $sessionData);
        $this->tpl->assign('pd_FrpId', $data['pd_FrpId']);
    }
}