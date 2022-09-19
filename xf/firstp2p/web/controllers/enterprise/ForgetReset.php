<?php
/**
 * 企业用户重置密码第三步
 * Created by PhpStorm.
 * User: yinli
 * Date: 2018/6/13
 * Time: 10:36
 */

namespace web\controllers\enterprise;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use libs\sms\SmsServer;

class ForgetReset extends BaseAction {
    public function init(){
        $this->form = new Form('post');
        $this->form->rules = array(
            'new_password' => array('filter' => 'string'),
            'confirmPassword' => array('filter' => 'string')
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
        $data = $this->form->data;
        $userId = \es_session::get('DoForgetPwdP2p_uid_2');
        if (empty($userId)) {
            $step = \es_session::get('DoForgetPwdP2p_step');
            $step = $step ?: '/enterprise/forgetPwd';
            return $this->show_error('请先验证身份', '错误', 0, 0, $step);
        }

        if ($_POST) {
            //code:'-1':令牌错误 ，0:请求成功， 2：图形验证码不正确 ， 3：证件号码不正确，4：手机号码格式不正确 5：新旧密码不一样
            // 验证表单令牌
            if(!check_token()) {
                $ret['code'] = '-1';
                $ret['msg'] = '令牌错误';
                return $this->show_error($ret, '', 1);
            }

            setlog(array('uid'=>$userId));

            //获取手机号码
            $user = $this->rpc->local('UserService\getUserByUserId', array($userId));
            $paymentUserId = empty($user['payment_user_id']) ? '' : $user['payment_user_id'];
            $userinfo = $this->rpc->local('EnterpriseService\getInfo', array($userId));
            $phone = empty($paymentUserId) ? $userinfo['contact']['major_mobile'] : $userinfo['contact']['consignee_phone'];
            if ($data['new_password'] !== $data['confirmPassword']) {
                $ret['code'] = '5';
                $ret['msg'] = '与登录密码不一致';
                $this->show_error($ret, '', 1);
                return false;
            }

            //密码检查
            //基本规则判断
            if ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1) {//先加上，等等再定是不是其他分站也使用
                $len = strlen($data['new_password']);
                $password = stripslashes($data['new_password']);
                \FP::import("libs.common.dict");
                $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
                //基本规则判断
                $base_rule_result=login_pwd_base_rule($len,$phone,$password);
                if ($base_rule_result){
                    $ret['code'] = '5';
                    $ret['msg'] = '密码不符合规则';
                    $this->show_error($ret,'',1,0);
                    return false;
                }
                //黑名单判断,禁用密码判断
                $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$phone);
                if ($forbid_black_result) {
                    $ret['code'] = '5';
                    $ret['msg'] = '密码不符合规则';
                    $this->show_error($ret,'',1,0);
                    return false;
                }
            }

            $bo = BOFactory::instance('web');
            $upResult = $bo->resetPwdCompany($userId, $data['new_password']);
            if ($upResult) {
                // 增加短信提示
                if (app_conf("SMS_ON") == 1) {
                    $msg_content = array(
                        'modify_time' => date("m-d H:i")
                    );
                    // SMSSend 用户找回密码短信 ， 企业用户不可以在前台找回密码
                    SmsServer::instance()->send($phone, 'TPL_SMS_ENTERPRISE_RESETPWD', $msg_content,$userId);
                }

                $ret['code'] = '0';
                $ret['msg'] = "密码修改成功。";
                $nextStep = '/enterprise/forgetSuc';
                \es_session::set('DoForgetPwdP2p_step', $nextStep);
                \es_session::set('DoForgetPwdP2p_uid_3', $userId);
                $this->show_success($ret, '', 1, 0, $nextStep);
                return true;
            } else {
                $ret['code'] = '5';
                $ret['msg'] = '重置密码失败';
                $this->show_error($ret,'',1,0);
                return false;
            }
        }
    }
}
