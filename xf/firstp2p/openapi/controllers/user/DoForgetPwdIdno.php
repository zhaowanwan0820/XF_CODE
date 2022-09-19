<?php

/**
 * @abstract openapi  忘记密码身份证号验证
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
class DoForgetPwdIdno extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'idno' => array("filter" => "required", "message" => "身份证号不能为空"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $mobile = \es_session::get('openapi_forgetpwd_mobile');
        $step = \es_session::get('openapi_forgetpwd_step');
        $userinfo = $this->rpc->local('UserService\getByMobile', array($mobile,'id,mobile,idcardpassed,idno'));
        RiskServiceFactory::instance(Risk::BC_CHANGE_PWD,Risk::PF_OPEN_API,$this->device)->check($userinfo,Risk::ASYNC,$data);
        setLog(array('uid'=>$userinfo['id']));
        //身份证号输入频率限制
        $old_check_idno_hours = Block::check('MODIFYPWD_CHECK_IDNO_HOURS',$userinfo['id'],true);
        if ($old_check_idno_hours === false) {
            $this->setErr('ERR_IDENTITY_NO_VERIFY','错误次数过多,请稍后重试');
            return false;
        }
        if (!$userinfo || $step['step'] < 3) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        } else {
            if ($userinfo['idno'] != $data['idno']) {
                $msg = '身份证号输入不正确';
                $old_check_idno_hours = Block::check('MODIFYPWD_CHECK_IDNO_HOURS',$userinfo['id'],false);//身份证号错误频率限制检查 
                if ($old_check_idno_hours === false) {
                    $msg = '错误次数过多,请稍后重试';
                }
                $this->setErr('ERR_PARAMS_ERROR',$msg);
                return false;
            }
            $step = \es_session::set('openapi_forgetpwd_step',array('step' => 4,'idno' => $step['idno']));
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

