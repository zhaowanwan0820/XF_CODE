<?php

/**
 * 忘记密码重置密码
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\UserService;
use core\service\user\UserLoginService;
use libs\sms\SmsServer;

class DoForgetPwdReset extends BaseAction {

    private $_error = null;

    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'new_password' => array(
                'filter' => 'required',
                'message' => '新密码不能为空'
            ),
            'confirmPassword' => array(
                'filter' => 'required',
                'message' => '确认新密码不能为空'
            )
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
        }
    }

    public function invoke() {
        // 验证表单令牌
        if(!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", 0, 0,url('user/Resetpwd'));
        }
        if (!empty($this->_error)) {
            $this->showForgetPasswordError();
            return false;
        }
        $data = $this->form->data;
        $data['phone'] = \es_session::get('DoForgetPwd_phone');//取出手机号，重置新密码
        $data['idno'] = \es_session::get('DoForgetPwd_idno');//取出身份证号验证
        $userinfo = UserService::getUserByMobile($data['phone'], 'id,idcardpassed,idno');
        if (!empty($data['idno']) && $data['idno'] != $userinfo['idno']){
            $this->_error = array('new_password' => '修改失败, 身份信息验证失败');
            return $this->showForgetPasswordError();
        }

        $uid = \es_session::get('DoForgetPwdP2p_uid');
        setlog(array('uid'=>$uid));
        setLog(array('user_phone' => array('phone' => user_name_format($data['phone']))));
        if (!\es_session::get('DoForgetPwd')) {
            $this->_error = array('new_password' => '修改失败');
            return $this->showForgetPasswordError();
        } elseif (\es_session::get(DoForgetPwd_idno_verify) == '1' && \es_session::get('DoForgetPwd') != '2') {//如果需要身份验证，则必须首先经过身份证验证
            $this->_error = array('new_password' => '修改失败');
            return $this->showForgetPasswordError();
        }
        if ($data['new_password'] !== $data['confirmPassword']) {
            $this->_error['confirmPassword'] = '确认密码与新密码不一致';
            $this->showForgetPasswordError();
            return false;
        }
        //密码检查
        //基本规则判断
        if ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1) {//先加上，等等再定是不是其他分站也使用
            $len = strlen($data['new_password']);
            $mobile = $data['phone'];
            $password = stripslashes($data['new_password']);
            \FP::import("libs.common.dict");
            $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
            //基本规则判断
            $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
            if ($base_rule_result){
                $this->_error = array('new_password' => '修改失败');
                return $this->showForgetPasswordError();
            }
            //黑名单判断,禁用密码判断
            $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
            if ($forbid_black_result) {
                $this->_error = array('new_password' => '修改失败');
                return $this->showForgetPasswordError();
            }
        }

        if (!$data['phone']) {
            return $this->show_error('', '验证错误', 1, 0,'user/ForgetPwd');
        }
        $upResult = UserLoginService::resetPwd($data['phone'], $data['new_password']);
        if ($upResult) {
            // 增加短信提示
            if (app_conf("SMS_ON") == 1) {
                $msg_content = array(
                    'modify_time' => date("m-d H:i")
                );
                // SMSSend 用户找回密码短信 ， 企业用户不可以在前台找回密码
                SmsServer::instance()->send($data['phone'], 'TPL_SMS_MODIFY_PASSWORD', $msg_content);
            }
            \es_session::delete('DoForgetPwd');//删除session，防止刷新多次请求
            return $this->show_success("密码修改成功，请重新登录。", '', 0, 0, url("user", "login"));
        } else {
            \es_session::delete('DoForgetPwd');//删除session，防止刷新多次请求
            $this->_error = array('new_password' => '修改失败');
            return $this->show_error('修改失败', "", 0, 0,url('user/ForgetPwd'));;
        }
    }

    private function showForgetPasswordError() {
        setLog(array('error_msg' => array('error' => json_encode($this->_error))));
        $this->tpl->assign("page_title", '忘记密码');
        $this->tpl->assign("error", $this->_error);
        $this->tpl->assign("data", $this->form->data);
        $this->tpl->assign('title', app_conf('SHOP_TITLE'));
        //$this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
        $this->template = "web/views/user/resetpwd.html";
        return;
    }
}