<?php

/**
 *
 * @abstract 验证短信验证码
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date   2016-4-28
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;

class CheckMCode extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "mobile" => array("filter" => "required", "message" => "请输入手机号"),
            "code" => array("filter" => "required", "message" => "请输入短信验证码"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR",$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($data['mobile'],180,0));
        if (!$data['code'] || $data['code'] != $vcode) {
            $this->setErr("ERR_SIGNUP_CODE");
            return false;
        }
        if (\es_session::get('openapi_modifypwd_token') && \es_session::get('openapi_modifypwd_step') >= 2) {
            \es_session::set('openapi_modifypwd_step', 3);
        }
        if (\es_session::get('openapi_forgetpwd_mobile') && \es_session::get('openapi_forgetpwd_step')['step'] >= 2) {
            $mobile = \es_session::get('openapi_forgetpwd_mobile');
            $userInfo = $this->rpc->local('UserService\getByMobile', array($data['mobile'],'id,mobile,id_type,idcardpassed,idno'));
            //$ret['is_verify_idno'] = $this->checkIdno($userInfo);
            \es_session::set('openapi_forgetpwd_step',array('step' => 3));
        }
        setLog(array('openapi_forgetpwd_step'=>\es_session::get('openapi_forgetpwd_step')));
        setLog(array('openapi_modifypwd_step'=>\es_session::get('openapi_modifypwd_step')));
        $this->rpc->local('MobileCodeService\delMobileCode', array($data['mobile'],0));//删除短信验证码
        $ret['msg'] = '短信验证码正确';
        $this->json_data = $ret;
        return true;
    }
    /**
     * 检查用户是否实名认证（只判断是否为大陆身份证认证，不是大陆身份证则按未实名处理）
     * @param unknown $userInfo
     * @return boolean
     */
    private function checkIdno($userInfo)
    {
        $flag = preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $userInfo['idno']);
        if ($userInfo['id_type'] ==1 && $userInfo['idcardpassed'] == 1 && $flag) {
            return true;
        } else {
            return false;
        }
    }
    public function authCheck()
    {
        return true;
    }
}
