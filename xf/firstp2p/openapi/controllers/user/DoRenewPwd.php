<?php

/**
 * @abstract openapi  修改密码
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-28
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Block;
use core\service\user\BOBase;
class DoRenewPwd extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'new_password' => array("filter" => "required", "message" => "新密码不能为空"),
            'confirmPassword' => array("filter" => "required", "message" => "确认密码不能为空"),
            'openId' => array("filter" => "string"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $this->form->data['oauth_token'] = \es_session::get('openapi_modifypwd_token');
        $data = $this->form->data;
        $step = \es_session::get('openapi_modifypwd_step');
        setLog(array('openapi_modifypwd_step'=>$step));
        if (!$data['oauth_token'] || $step < 3) {
            $this->setErr('ERR_SYSTEM_ACTION_PERMISSION');
            return false;
        }
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        } else {
            $userInfo = $userInfo->toArray();
            //弱密码校验
            \FP::import("libs.common.dict");
            $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
            $mobile = $userInfo['mobile'];
            $password = $data['new_password'];
            $len=strlen($password);
            //基本规则判断
            $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
            if ($base_rule_result){
                $this->setErr('ERR_PASSWORD_ILLEGAL',$base_rule_result['errorMsg']);
                return false;
            }
            //黑名单判断,禁用密码判断
            $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
            if ($forbid_black_result) {
                $this->setErr('ERR_PASSWORD_ILLEGAL',$forbid_black_result['errorMsg']);
                return false;
            }
            $pwd = ((new BOBase())->compilePassword($data['new_password']));
            if ($userInfo['userPwd'] == $pwd) {
                $this->setErr('ERR_MANUAL_REASON','新密码不能和旧密码相同');
                return false;
            }
            if ($data['new_password'] != $data['confirmPassword']) {
                $this->setErr('ERR_MANUAL_REASON','两次密码输入不一致');
                return false;
            }
            $result = $this->rpc->local('UserService\updateInfo', array(array('id'=>$userInfo['userId'], 'user_pwd'=>$pwd)));
            if ($result) {
                $req = \es_session::get('openapi_modifypwd_query');
                $redirect_uri = $req['redirect_uri'];
                $ret['msg'] = '密码修改成功';
                $ret['redirect_uri'] = $redirect_uri;
                \es_session::delete('openapi_modifypwd_token');
                \es_session::delete('openapi_modifypwd_step');
            } else {
                $this->setErr('ERR_MANUAL_REASON','密码修改失败');
                return false;
            }
            $this->json_data = $ret;
            return true;
        }
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

