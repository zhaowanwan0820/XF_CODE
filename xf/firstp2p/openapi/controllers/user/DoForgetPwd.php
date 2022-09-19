<?php

/**
 * @abstract openapi  忘记密码
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-28
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Block;
use core\service\user\BOBase;

use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
class DoForgetPwd extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'mobile' => array("filter" => "required", "message" => "手机号不能为空"),
            'idno' => array("filter" => "string",  "option" => array("optional" => true)),
            'verify' => array("filter" => "reg", "message" => '验证码不正确', "option" => array("regexp" => "/^[0-9a-zA-Z]{4,10}$/")),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $step = \es_session::get('openapi_forgetpwd_step');
        $userinfo = $this->rpc->local('UserService\getByMobile', array($data['mobile'],'id,mobile,idcardpassed,is_effect,is_delete'));
        setLog(array('uid'=>$userinfo['id']));
        RiskServiceFactory::instance(Risk::BC_CHANGE_PWD,Risk::PF_OPEN_API,$this->device)->check($userinfo,Risk::ASYNC,$data);
        if (false && $step['step'] < 1) {
            $this->setErr('ERR_SYSTEM_ACTION_PERMISSION');
            return false;
        } else {
//             if (!$userinfo || $userinfo['is_effect'] == 0 || $userinfo['is_delete'] == 1) {
//                 $this->setErr('ERR_PARAMS_ERROR','请输入现在绑定的手机号');
//                 return false;
//             }

            $idno = null;
            if($userinfo['idcardpassed'] == 1){
                if(empty($data['idno'])){
                    $this->setErr('ERR_PARAMS_ERROR','请输入绑定的证件号');
                    return false;
                }
                $idno = $data['idno'];
            }
            //验证图形验证码
            $verify = \es_session::get('verify');
            \es_session::set('verify', 'xxx removeVerify xxx');
            // 校验验证码
            $captcha = $this->form->data['verify'];
            if (md5($data['verify']) !== $verify || !$data['verify']) {
                $this->setErr('ERR_VERIFY_ILLEGAL');
                return false;
            }
            $errno = $this->rpc->local('MobileCodeService\isSend', array($data['mobile'], 2));
            if ($errno != 1) {
                $err = $this->rpc->local('MobileCodeService\getError', array($errno));
                $this->setErr('ERR_MANUAL_REASON',$err['message']);
                return false;
            }
            $send_code = $this->rpc->local('MobileCodeService\sendVerifyCode', array($data['mobile'], 0, false, 2, 'cn', $idno));
            $send_code = json_decode($send_code,true);
            if ($send_code['code'] != 1) {
                $this->setErr('ERR_MANUAL_REASON',$send_code['message']);
                return false;
            }
            $step = \es_session::set('openapi_forgetpwd_step',array('step' => 2));
            \es_session::set('openapi_forgetpwd_mobile',$data['mobile']);
            setLog(array('openapi_forgetpwd_step'=>\es_session::get('openapi_forgetpwd_step')));
            $ret['msg'] = '参数验证成功';
            $this->json_data = $ret;
            RiskServiceFactory::instance(Risk::BC_CHANGE_PWD,Risk::PF_OPEN_API)->notify();
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

