<?php

namespace web\controllers\enterprise;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\UserService;
use core\service\EnterpriseService;

/**
 * 企业用户重置密码 - 第二步（验证身份）
 */
class ForgetIdentity extends BaseAction {
    public function init(){
        $this->form = new Form();
        $this->form->rules = array(
            'mobile' => array('filter' => 'string'),
            'major_condentials_no' => array('filter' => 'string'),
            'legalbody_credentials_no' => array('filter' => 'string'),
            'viewContent' => array('filter' => 'int')
        );

        if (!$this->form->validate()) {
            $ret['code'] = '4';
            $ret['msg'] = $this->form->getErrorMsg();
            $ret['data'] = $this->form->data;
            $this->show_error($ret, '', 1);
            return false;
        }
    }

    public function invoke(){
        //code:'-1':令牌错误 ，0:请求成功, 2：图形验证码不正确 ， 3：证件号码不正确，4：手机号码格式不正确，5：联系方式输入错误
        $data = $this->form->data;
        $userId = \es_session::get('DoForgetPwdP2p_uid_1');
        if (empty($userId)) {
            $step = \es_session::get('DoForgetPwdP2p_step');
            $step = $step ?: '/enterprise/forgetPwd';
            return $this->show_error('请先填写用户名', '错误', 0, 0, $step);
        }

        if ($_POST) {
            $ret = array();
            // 验证表单令牌
            if (!check_token()) {
                $ret['code'] = '-1';
                $ret['msg'] = '令牌错误';
                return $this->show_error($ret, '', 1, 0, '/enterprise/ForgetIdentity');
            }

            if (!preg_match("/^1[3456789]\d{9}$/", $data['mobile'])) {
                $ret['code'] = '4';
                $ret['msg'] = '手机号码格式错误';
                $this->show_error($ret, '', 1);
                return false;
            }

            $viewContent = $data['viewContent'];
            $userinfo = $this->rpc->local('EnterpriseService\getInfo', array($userId));
            if($viewContent == 1) {
                //验证业务接洽联系方式是否正确
                if($data['mobile'] !== $userinfo['contact']['consignee_phone']) {
                    $ret['code'] = '5';
                    $ret['msg'] = '联系方式输入错误';
                    $this->show_error($ret,'',1,0);
                    return false;
                }
            } elseif ($viewContent == 2) {
                //验证法定代表人手机号（后台对应字段为代理人手机号）、证件号是否正确
                if($data['mobile'] !== $userinfo['contact']['major_mobile']) {
                    $ret['code'] = '5';
                    $ret['msg'] = '法定代表人联系方式输入错误';
                    $this->show_error($ret,'',1,0);
                    return false;
                }

                $legalbody_credentials_no = $data['legalbody_credentials_no'];
                if($legalbody_credentials_no !== $userinfo['base']['legalbody_credentials_no']) {
                    $ret['code'] = '3';
                    $ret['msg'] = '法定代表人证件号输入错误';
                    $this->show_error($ret,'',1,0);
                    return false;
                }
            } elseif ($viewContent == 3) {
                //验证代理人手机号、证件号，法定代表人证件号是否正确
                if($data['mobile'] !== $userinfo['contact']['major_mobile']) {
                    $ret['code'] = '5';
                    $ret['msg'] = '代理人联系方式输入错误';
                    $this->show_error($ret,'',1,0);
                    return false;
                }

                $major_condentials_no = $data['major_condentials_no'];
                if($major_condentials_no !== $userinfo['contact']['major_condentials_no']) {
                    $ret['code'] = '3';
                    $ret['msg'] = '代理人证件号输入错误';
                    $this->show_error($ret,'',1,0);
                    return false;
                }

                $legalbody_credentials_no = $data['legalbody_credentials_no'];
                if($legalbody_credentials_no !== $userinfo['base']['legalbody_credentials_no']) {
                    $ret['code'] = '3';
                    $ret['msg'] = '法定代表人证件号输入错误';
                    $this->show_error($ret,'',1,0);
                    return false;
                }
            }

            $ret['code'] = '0';
            $ret['msg'] = '身份认证通过';
            $nextStep = '/enterprise/forgetReset';
            \es_session::set('DoForgetPwdP2p_step', $nextStep);
            \es_session::set('DoForgetPwdP2p_uid_2', $userId);
            $this->show_success($ret, '', 1, 0, $nextStep);
            return true;
        }

        $userinfo = $this->rpc->local('UserService\getUserByUserId', array($userId));
        if ($userinfo) {
            $payment_user_id = $userinfo['payment_user_id'];
        }

        if (empty($payment_user_id)) {
            $viewContent = 1;
        } else {
            //判断操作人类型
            $is_same_operator = $this->rpc->local('EnterpriseService\isSameOperator', array($userId));
            if ($is_same_operator) {
                $viewContent = 2;
            } else {
                $viewContent = 3;
            }
        }

        $this->tpl->assign("VIEW_CONTENT",$viewContent);
    }
}
