<?php
/**
 * 忘记密码页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use libs\sms\SmsServer;

class DoForgetPwd extends BaseAction {

    private $_error = null;

    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
                'phone' => array(
                        'filter' => 'reg',
                        "message" => "手机号码格式不正确",
                        "option" => array(
                            "regexp" => "/^1[3456789]\d{9}$/"
                        )
                ),
                'code' => array(
                        'filter' => 'required',
                        'message' => '验证码不能为空'
                ),
                'password' => array(
                        'filter' => 'required',
                        'message' => '密码不能为空'
                ),
                'confirmPassword' => array(
                        'filter' => 'required',
                        'message' => '确认密码不能为空'
                )
        );
        if (!empty($_REQUEST['country_code']) && isset($GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]) && $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['is_show']){
            $this->form->rules['phone'] =  array(
                'filter' => 'reg',
                "message" => "手机格式错误",
                "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['regex']}/")
            );
        }
        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
        }
    }

    public function invoke() {
        if (!empty($this->_error)) {
            $this->showForgetPasswordError();
            return false;
        }
        $data = $this->form->data;
        if (empty($data['code'])) {
            $this->_error['code'] = '验证码不能为空';
            $this->showForgetPasswordError();
            return false;
        }
        if ($data['password'] !== $data['confirmPassword']) {
            $this->_error['confirmPassword'] = '确认密码与新密码不一致';
            $this->showForgetPasswordError();
            return false;
        }
        $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($data['phone']));

        if ($vcode != $this->form->data['code']) {
            $this->_error = array('code' => '验证码不正确');
            $this->showForgetPasswordError();
            return false;
        }
        $this->rpc->local('MobileCodeService\delMobileCode', array($data['phone']));
        //密码检查
        //基本规则判断
        if ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1) {
            $len = strlen($data['password']);
            $mobile = $data['phone'];
            $password = stripslashes($data['password']);
            \FP::import("libs.common.dict");
            $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
            //基本规则判断
            $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
            if ($base_rule_result){
                $this->_error = array('password' => '修改失败');
                return $this->showForgetPasswordError();
            }
            //黑名单判断,禁用密码判断
            $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
            if ($forbid_black_result) {
                $this->_error = array('password' => '修改失败');
                return $this->showForgetPasswordError();
            }
        }

        $bo = BOFactory::instance('web');
        $upResult = $bo->resetPwd($data['phone'], $data['password']);
        if ($upResult) {
            // 增加短信提示
            if (app_conf("SMS_ON") == 1) {
                $msg_content = array(
                    'account_title' => '',
                    'modify_time' => date("m-d H:i")
                );
                // SMSSend 用户找回密码短信 ， 企业用户不可以在前台找回密码
                SmsServer::instance()->send($data['phone'], 'TPL_SMS_MODIFY_PASSWORD_NEW', $msg_content);

            }
            return $this->show_success("密码修改成功，请重新登录。", '', 0, 0, url("user", "login"));
        } else {
            $this->_error = array('password' => '修改失败');
            return $this->showForgetPasswordError();
        }
    }

        private function showForgetPasswordError() {
            $this->tpl->assign("page_title", '忘记密码');
            $this->tpl->assign("error", $this->_error);
            $this->tpl->assign("data", $this->form->data);
            $this->tpl->assign('title', app_conf('SHOP_TITLE'));
            $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
            $this->template = "web/views/user/forgetpassword.html";
            return;
        }
}
