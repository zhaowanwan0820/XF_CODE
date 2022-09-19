<?php

/**
 * Signup.php
 *
 * @date 2014-05-04
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Monitor;
use core\service\user\UserService;
use core\service\sms\MobileCodeService;

/**
 * 注册添加用户
 *
 * Class Signup
 * @package api\controllers\user
 */
class Signup extends AppBaseAction {
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();

        $this->form = new Form("post");
        $this->form->rules = array(
            'username' => array('filter' => 'string',),
            'password' => array("filter" => 'required'),
            'email' => array("filter" => 'string'),
            'phone' => array("filter" => 'required'),
            'country_code' => array("filter" => "string",'option' => array('optional' => true)),
            'code' => array("filter" => 'required'),
            'invite' => array("filter" => 'string'),    // 邀请码
            'site_id' => array("filter" => 'string'),   // 分站标示
            'euid' => array("filter" => 'string'),      // 分站标示
            'country_code' => array("filter" => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        if (app_conf("TURN_ON_FIRSTLOGIN") == 2) {
            $this->setErr('ERR_SYSTEM', "系统正在升级，暂停注册，预计时间0:00到4:00");
        }

        $params = $this->form->data;
        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER, Risk::PF_API, Risk::getDevice($_SERVER['HTTP_OS']))
            ->check($params,Risk::SYNC);

        // Risk检查
        if ($ret === false) {
            Monitor::add('REGISTER_FAIL');
            $this->setErr('ERR_RISK_DEVICE_BLACKLIST', '注册异常');
        }

        // 进一步参数验证
        if (!empty($params['username']) && !$this->check_username()) {
            return false;
        }

        if (!empty($params['email']) && !$this->check_email()) {
            return false;
        }

        if (!$this->check_password() || !$this->check_code()) {
            return false;
        }

        if(!$this->check_phone()){
            return false;
        }

        // 是否开启验证码效验，方便测试
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $mobileCodeServiceObj = new MobileCodeService();
            $vcode = $mobileCodeServiceObj->getMobilePhoneTimeVcode($params['phone'], 60, 0);
            if ($vcode != trim($params['code'])) {
                $this->setErr('ERR_SIGNUP_CODE');
            }

            $mobileCodeServiceObj->delMobileCode($params['phone'], 0);
        }

        // 手机号已经验证过了
        $params['use_mobile_code'] = false;
        // 5为普惠wap，4为普惠app
        $fromPlatform = $this->isWapCall() ? 5 : 4;
        $params['from_platform'] = $fromPlatform;
        $result = UserService::userRegister($params);
        if ($result === false) {
            $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
        }

        RiskServiceFactory::instance(Risk::BC_REGISTER, Risk::PF_API)
            ->notify(array('userId'=>$result['uid']), $params);

        $this->json_data = $result;
    }

    /**
     * 校验用户名
     * 4-16个字符，支持英文或英文与数字，下划线，横线组合
     *
     * @return bool
     */
    public function check_username() {
        $reg = "/^([A-Za-z])[\w-]{3,15}$/";
        if (!preg_match($reg, $this->form->data['username'])) {
            $this->setErr('ERR_SIGNUP_PARAM_USERNAME');
        }
    }

    /**
     * 校验密码
     * 5-25个字符，任意字符组成的非空字符串
     *
     * @return bool
     */
    public function check_password() {
        $reg = "/^.{6,20}$/";
        $password_trim = trim($this->form->data['password']);
        if (!empty($password_trim) && preg_match($reg, $this->form->data['password'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_PASSWORD');
        }
    }

    /**
     * 校验手机号
     *
     * @return bool
     */
    public function check_phone() {
        $countryCode = 'cn';
        if ((isset($this->form->data['country_code'])) && (!empty($this->form->data['country_code']))){
            $countryCode = $this->form->data['country_code'];
        }

        $mobileCode  = $GLOBALS['dict']['MOBILE_CODE'];
        $mobileReg = $mobileCode[$countryCode]['regex'];
        if(empty($mobileReg)){
            return false;
        }

        $reg = "/".$mobileReg."/";
        if (preg_match($reg, $this->form->data['phone'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_PHONE');
        }
    }

    /**
     * 校验email
     *
     * @return bool
     */
    public function check_email() {
        if (check_email($this->form->data['email'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_EMAIL');
        }
    }

    /**
     * 校验手机验证码
     *
     * @return bool
     */
    public function check_code() {
        $reg = "/^\w{4,20}$/";
        if (preg_match($reg, $this->form->data['code'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_CODE');
        }
    }
}
