<?php

/**
 * CheckRegister.php
 * 注册时  校验手机号和邀请码以及其他信息
 */

namespace api\controllers\enterprise;

use libs\web\Form;
use libs\utils\Monitor;
use libs\utils\Risk;
use api\controllers\BaseAction;
use api\controllers\enterprise\DoRegister;
use core\service\risk\RiskServiceFactory;

class CheckRegister extends DoRegister {

    protected $useSession = true;

    public function init() {
        // 获取基类
        $grandParent = self::getRoot();
        $grandParent::init();

        $this->form = new Form("post");
        $this->form->rules = array(
            // 企业会员登录名
            'user_name' => array('filter' => 'reg', 'message' => '请输入4-20位字母、数字、下划线、横线，首位只能为字母', "option" => array("regexp" => "/^([A-Za-z])[\w-]{3,19}$/", 'optional' => true)),
            // 密码
            'password' => array('filter' => 'length', 'message' => '密码应为6-20位数字/字母/标点', "option" => array("min" => 6, "max" => 20)),
            // 接受短信手机号
            'sms_phone' => array(
                'filter' => 'reg',
                "message" => "接收短信通知手机号码应为7-11为数字",
                "option" => array("regexp" => "/^1[3456789]\d{9}$/")
            ),
            // 接收短信号码国别码
            'sms_country_code' => array('filter' => 'string'),
            // 推荐人姓名
            'inviter_name' => array('filter' => 'string'),
            // 推荐人邀请码
            'invite' => array('filter'=>'string'),
            // 图形验证码
            'verify' => array('filter' => 'required', 'message' => '图形验证码不能为空'),
            // 注册协议
            'agreement' => array('filter' => 'string'),
        );

        if (!empty($_REQUEST['sms_country_code']) && isset($GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]) && $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['is_show']){
            $this->form->rules['sms_phone'] =  array(
                'filter' => 'reg',
                "message" => "手机格式错误",
                "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['regex']}/")
            );
            $this->_isHaveCountyCode = true;
        }

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        // 先验证验证码逻辑
        if (empty($data['verify'])) {
            Monitor::add('REGISTER_FAIL');
            $this->setErr('ERR_MANUAL_REASON', '验证码错误');
            return false;
        }

        $sessionId = session_id();
        $verify = \SiteApp::init()->cache->get("verify_" . $sessionId);
        \SiteApp::init()->cache->delete("verify_" . $sessionId);
        $data['verify'] = strtolower($data['verify']);
        if ($verify != md5($data['verify'])) {
            Monitor::add('REGISTER_FAIL');
            $this->setErr('ERR_VERIFY_ILLEGAL');
            return false;
        }

        if (!$this->checkRegister()) {
            return false;
        }

        $this->json_data = array('result'=>true);
    }
}
