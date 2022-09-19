<?php

/**
 * @abstract openapi  邮箱设置
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-08-26
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\MsgConfigService;
use core\service\user\BOFactory;

class DoSetEmail extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'email' => array("filter" => "email", 'message'=>'请填写有效的邮箱地址',"option" => array("optional" => true)),
            'password' => array("filter" =>"length",'message' => '密码长度为5-25位',"option" => array("min" => 5,"max" => 25),"option" => array("optional" => true)),
            'is_modify_email' => array("filter" => "int","option" => array("optional" => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $this->form->data['oauth_token'] = \es_session::get('openapi_modifymail_token');
        $this->form->data['redirect_uri'] = \es_session::get('openapi_modifymail_redirect_uri') ? \es_session::get('openapi_modifymail_redirect_uri') : '';
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);

        if ($data['is_modify_email'] == 1) {
            $this->tpl->assign("redirect_uri", $data['redirect_uri']);
            $this->template = $this->template = "openapi/views/user/do_set_email.html";
            return;
        }
        if($data['password']) {
            $bo = BOFactory::instance('web');
            $pwdCompile = $bo->compilePassword($data['password']);
            if($pwdCompile !== $userInfo->userPwd) {
                $this->setErr('ERR_AUTH_FAIL','密码输入错误');
                return false;
            }
        }
        $isExist = $this->rpc->local('UserService\checkEmailExist', array($data['email']));
        if($isExist){
            $this->setErr('ERR_SIGNUP_EMAIL_UNIQUE','该邮箱已被使用');
            return false;
        }
        // 验证表单令牌
        if(!check_token()) {
            $this->setErr('ERR_TOKEN_ERROR',$GLOBALS['lang']['TOKEN_ERR']);
            return false;
        }
        $isSetEmail = $userInfo->getEmail();
        if (empty($isSetEmail)) {
            //事物开始 邮箱设置并默认给用户发送月对账单和投资合同
            $GLOBALS['db']->startTrans();

            $saveData = array('id' => $userInfo->userId, 'email' => $data['email']);
            $save = $this->rpc->local('UserService\updateInfo', array($saveData));
            if (!$save) {
                $GLOBALS['db']->rollback();
                $this->setErr('ERR_EMAIL_SET','设置邮箱失败，稍后重试！');
                return false;
            }
            // 邮件订阅设置,默认给用户发送月对账单和投资合同
            $userSetService = new MsgConfigService();
            $emailConfig = array(
                    '32' => '1',//合同下发
                    '33' => '1',//月对账单
            );
            $ret = $userSetService->setSwitches($userInfo->userId, 'email_switches', $emailConfig);
            if (!$ret) {
                $GLOBALS['db']->rollback();
                $this->setErr('ERR_EMAIL_SET','设置邮箱失败，稍后重试！');
                return false;
            }
            $GLOBALS['db']->commit();
        } else {
            $saveData = array('id' => $userInfo->userId, 'email' => $data['email']);
            $save = $this->rpc->local('UserService\updateInfo', array($saveData));
            if (!$save) {
                $this->setErr('ERR_EMAIL_SET','设置邮箱失败，稍后重试！');
                return false;
            }
        }

        $result['success'] = '邮箱设置成功！';
        $result['redirect_uri'] = $data['redirect_uri'];
        \es_session::delete('openapi_modifymail_token');
        \es_session::delete('openapi_modifymail_redirect_uri');
        $this->json_data = $result;
    }
    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }
    public function authCheck() {
        return true;
    }
}
