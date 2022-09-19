<?php

namespace web\controllers\enterprise;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\UserModel;

/**
 * 企业用户找回密码 - 第一步（填写用户名）
 */
class ForgetPwd extends BaseAction {
    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'username' => array('filter' => 'string'),
            'captcha' => array('filter' => 'string')
        );

        if (!$this->form->validate()) {
            $ret['code'] = '4';
            $ret['msg'] = $this->form->getErrorMsg();
            $ret['data'] = $this->form->data;
            $this->show_error($ret, '', 1);
            return false;
        }
    }

    public function invoke() {
        if($_POST) {
            $data = $this->form->data;
            $ret = array();
            // 验证表单令牌
            if (!check_token()) {
                $ret['code'] = '-1';
                $ret['msg'] = '令牌错误';
                $this->show_error($ret, '', 1);
                return false;
            }

            if (empty($data['username'])) {
                $ret['code'] = '2';
                $ret['msg'] = '该用户名不存在';
                $this->show_error($ret, '', 1);
                return false;
            }

            $userinfo = $this->rpc->local('UserService\getUserinfoByUsername', array($data['username']));
            if (!$userinfo) {
                $ret['code'] = '2';
                $ret['msg'] = '该用户名不存在';
                $this->show_error($ret, '', 1);
                return false;
            }

            if ($userinfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                $ret['code'] = '0';
                $ret['msg'] = '用户验证成功';

                $nextStep = '/enterprise/forgetIdentity';
                \es_session::set('DoForgetPwdP2p_step', $nextStep);
                \es_session::set('DoForgetPwdP2p_uid_1', $userinfo['id']);
                return $this->show_success($ret, '', 1, 0, $nextStep);
            } elseif (!empty($userinfo['mobile']) && substr($userinfo['mobile'], 0, 1) == 6 && $userinfo['mobile_code'] == '86') {
                $ret['code'] = '-1';
                $ret['msg'] = '请拨打客户服务热线：010-89920015(工作时间：周一至周五9:30-18:30)，客服专员将第一时间为您处理';
                $this->show_error($ret, '', 1);
                return false;
            } elseif ($userinfo['user_type'] == UserModel::USER_TYPE_NORMAL) {
                $ret['code'] = '-1';
                $ret['msg'] = '非企业会员请使用个人会员找回密码功能';
                $this->show_error($ret, '', 1);
                return false;
            }
        }
    }
}
