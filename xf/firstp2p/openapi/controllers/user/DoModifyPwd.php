<?php

/**
 * @abstract openapi  修改密码
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-26
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Block;
use core\service\user\BOBase;
use core\service\LogRegLoginService;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
class DoModifyPwd extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'old_password' => array("filter" => "required", "message" => "密码不能为空"),
            'mobile' => array("filter" => "required", "message" => "手机号不能为空"),
            'openId' => array("filter" => "string"),
            'verify' => array("filter" => "reg", "message" => '验证码错误', "option" => array("regexp" => "/^[0-9a-zA-Z]{4,10}$/")),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $step = \es_session::get('openapi_modifypwd_step');
        if ($step < 1) {
            $this->setErr("ERR_SYSTEM_ACTION_PERMISSION");
            return false;
        }

        $this->form->data['oauth_token'] = \es_session::get('openapi_modifypwd_token');
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        } else {
            RiskServiceFactory::instance(Risk::BC_CHANGE_PWD,Risk::PF_OPEN_API,$this->device)->check($userInfo,Risk::ASYNC,$data);
            $userInfo = $userInfo->toArray();
            //旧密码输入错误频率限制 
            $old_check_hours = Block::check('OLDPWD_CHECK_HOURS',$userInfo['userId'],true);
            if ($old_check_hours ===false) {
                $this->setErr('ERR_AUTH_FAIL','错误次数过多,请稍后重试');
                return false;
            }
            $pwd = (new BOBase())->compilePassword($data['old_password']);
            if ($userInfo['userPwd'] != $pwd) {
                $msg = '旧密码输入错误，请重新输入';
                $old_check_hours = Block::check('OLDPWD_CHECK_HOURS',$userInfo['userId'],false);//旧密码输入错 误频率限制检查 
                if ($old_check_hours === false) {
                $msg = '错误次数过多,请稍后重试';
                }
                $this->setErr('ERR_AUTH_FAIL',$msg);
                return false;
            }
            if ($userInfo['mobile'] != $data['mobile']) {
                $this->setErr('ERR_PARAMS_ERROR','请输入现在绑定的手机号');
                return false;
            }
            //验证图形验证码
            $verify = \es_session::get('verify');
            \es_session::set('verify', 'xxx removeVerify xxx');
            // 校验验证码
            $captcha = $this->form->data['verify'];
            if (!$data['verify'] || md5($data['verify']) !== $verify) {
                $this->setErr('ERR_VERIFY_ILLEGAL');
                return false;
            }
            $errno = $this->rpc->local('MobileCodeService\isSend', array($data['mobile'], 12));
            if ($errno != 1) {
                $err = $this->rpc->local('MobileCodeService\getError', array($errno));
                $this->setErr('ERR_MANUAL_REASON',$err['message']);
                return false;
            }
            $send_code = $this->rpc->local('MobileCodeService\sendVerifyCode', array($data['mobile'], 0, false, 12));
            $send_code = json_decode($send_code,true);
            if ($send_code['code'] != 1) {
                $this->setErr('ERR_MANUAL_REASON',$send_code['message']);
                return false;
            }
            \es_session::set('openapi_modifypwd_step', 2);
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

    public function authCheck()
    {
        return true;
    }

}

