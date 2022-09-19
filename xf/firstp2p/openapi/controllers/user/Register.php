<?php

/**
 * 新用户注册页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace openapi\controllers\user;

use openapi\controllers\BaseAction;

class Register extends BaseAction {

    const IS_H5 = true;

    private $agreementAddress = array(
        'firstp2p' => 'http://www.firstp2p.com/register_terms_h5.html',
    );
    
    public function init() {
        
    }

    /**
     * 获取注册协议地址
     * @param $key 站点key
     * @return  string
     *
     * */
    public function getAgreementAddress($key = '') {
        if (!empty($key) && isset($this->agreementAddress[$key])) {
            return $this->agreementAddress[$key];
        } else {
            return $this->agreementAddress['firstp2p'];
        }
    }

    /**
     *
     * 获取邀请码开关状态
     * @return int
     * */
    public function getInviteMoney() {
        $turn_on_invite = app_conf('TURN_ON_INVITE');
        if ($turn_on_invite == '1') {
            return app_conf('REGISTER_REBATE_MONEY');
        } else {
            return '-1';
        }
    }

    public function invoke() {
        $turnOn = app_conf('TURN_ON_FIRSTLOGIN');
        //系统正在维护中
        if ($turnOn == '2') {
            $this->setErr('ERR_SYSTEM_MAINTENANCE');
            return false;
        }
        $agreement = $this->getAgreementAddress(app_conf('APP_SITE'));
        $this->tpl->assign('invite_money', $this->getInviteMoney());

        //$cn = trim(\es_cookie::get(\core\service\CouponService::LINK_COUPON_KEY));
        //$cn = $cn ? $cn : $this->form->data['cn'];
        $this->tpl->assign("page_title", '注册');
        $this->tpl->assign("agreement", $agreement);
        //$this->tpl->assign("cn", $cn);
        $this->tpl->assign("website", app_conf('SHOP_TITLE'));
        $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
        $this->template = "openapi/views/user/register.html";
    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

}
