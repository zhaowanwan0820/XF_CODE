<?php

/**
 * @abstract openapi  忘记密码重置密码
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-28
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Block;
use core\service\user\BOBase;

class DoResetPwd extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'new_password' => array("filter" => "required", "message" => "新密码不能为空"),
            'confirmPassword' => array("filter" => "required", "message" => "确认密码不能为空"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $step = \es_session::get('openapi_forgetpwd_step');
        setLog(array('openapi_forgetpwd_step'=>\es_session::get('openapi_forgetpwd_step')));

        if ($step['step'] == 4) {
            $this->doreset();
            return;
        } /* elseif ($step['idno'] == false && $step['step'] == 4) {
            $this->doreset();
            return;
        }  */else {
            $this->setErr('ERR_SYSTEM_ACTION_PERMISSION');
            return false;
        }
    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

    public function doreset() {
        $data = $this->form->data;
        $mobile = \es_session::get('openapi_forgetpwd_mobile');
        $userinfo = $this->rpc->local('UserService\getByMobile', array($mobile,'id,mobile,idcardpassed,idno'));
        setLog(array('uid'=>$userinfo['id']));
        if (!$userinfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        } else {
            if ($data['new_password'] != $data['confirmPassword']) {
                $this->setErr('ERR_MANUAL_REASON','两次密码输入不一致');
                return false;
            }
            //弱密码校验
            \FP::import("libs.common.dict");
            $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
            $mobile = $userinfo['mobile'];
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
            $result = $this->rpc->local('UserService\updateInfo', array(array('id' => $userinfo['id'], 'user_pwd' => $pwd)));
            if ($result) {
                $ret['msg'] = '密码修改成功';
                \es_session::delete('openapi_forgetpwd_query');
                \es_session::delete('openapi_forgetpwd_step');
                \es_session::delete('openapi_forgetpwd_mobile');
            } else {
                $this->setErr('ERR_MANUAL_REASON','密码修改失败');
                return false;
            }
            $this->json_data = $ret;
            return true;
        }
    }
    public function authCheck() {
        return true;
    }

}

