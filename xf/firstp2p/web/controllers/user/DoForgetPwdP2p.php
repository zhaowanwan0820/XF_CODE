<?php

/**
 * 忘记密码验证手机号和图形验证码页面
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\PassportService;

class DoForgetPwdP2p extends BaseAction {

    private $_error = null;

    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'phone' => array(
                'filter' => 'reg',
                "message" => "手机号码格式不正确",
                "option" => array(
                    "regexp" => "/^1[3456789]\d{9}$/"
                )
            ),
            'idno' => array(
                'filter' => 'string',
            ),
            'captcha' => array('filter' => 'string'),
            //'ajax' => array('filter' => 'string'),//1：异步校验手机号是否为现绑定的手机号相同
            'country_code' => array('filter' => 'string')
        );
        if (!empty($_POST['country_code']) && isset($GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]) && $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['is_show']){
            $this->form->rules['phone'] =  array(
                'filter' => 'reg',
                "message" => "手机格式错误",
                "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['regex']}/")
            );
        }
        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
            $ret['code'] = '4';
            $ret['msg'] = $this->_error['phone'];
            $ret['data'] = $this->form->data;
            $this->show_error($ret,'',1,0);
            return false;
        }
    }

    public function invoke() {
        //code:'-1':令牌错误 ，0:请求成功，  1：请输入现在绑定的手机号，2：图形验证码不正确 ， 3：没有进行实名认证，4：手机号码格式不正确
        $data = $this->form->data;
        // 验证表单令牌
        if(!check_token()) {
            $ret['code'] = '-1';
            $ret['msg'] = 'error_jump';
            return $this->show_error($ret,'',1,0,'/user/ForgetPwd');
        }

        // TODO非本地通行证，禁止理财修改密码
        $passportService = new PassportService();
        if ($bizInfo = $passportService->isThirdPassport($data['phone'])) {
            $ret['code'] = '-2';
            $ret['address'] = $bizInfo['platformName'];
            $ret['location'] = $bizInfo['url'];
            return $this->show_error($ret,'',1,0,'/user/ForgetPwd');
        }

        //校验图形验证码
        $verify = \es_session::get('verify');
        if (!empty($verify)) {
            // 验证码校验失败，立刻将session中verify设置成非MD5值
            \es_session::set('verify','xxx removeVerify xxx');
            $captcha = $data['captcha'];
            if (md5($captcha) !== $verify) {
                $ret['code'] = '2 ';
                $ret['msg'] = '图形验证码不正确';
                $this->show_error($ret,'',1,0);
                return false;
            }
        } else {
            $ret['code'] = '2 ';
            $ret['msg'] = '图形验证码不正确';
            $this->show_error($ret,'',1,0);
            return false;
        }

        $ret['code'] = '0';
        $ret['mobile_code'] = $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$data['country_code']]['code'];
        $ret['idno'] = $data['idno'];
        $this->show_success($ret,'',1,0,'/user/Resetpwd');
        return;

        $country_code = $data['country_code'];
        $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$data['country_code']]['code'];
        $condition = "mobile = '".$data['phone']."'";
        if ($country_code && $mobile_code) {
            $condition = $condition." and country_code = '".$country_code."'";
        }

        //无效账号过滤
        $condition .= " and is_effect = 1 and is_delete = 0";

        //判断是不是现在绑定的手机号
        $userinfo = $this->rpc->local('UserService\getUserByCondition', array($condition,'id,idcardpassed,idno,id_type'));
        setlog(array('uid'=>$userinfo['id']));
        \es_session::set('DoForgetPwdP2p_uid',$userinfo['id']);
        if (!$userinfo) {
            $ret['code'] = '-1';
            $ret['msg'] = '请输入现在绑定的手机号';
            \es_session::set('Phone_not_exist', '请输入现在绑定的手机号');
            $this->show_error($ret,'',1,0);
            return false;
        }

        //判断有没有实名认证
        //将非大陆身份证号归为没有实名认证的类别，即跳过身份证验证页面
        $flag = preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $userinfo['idno']);//匹配身份证是不是18位
        if ($userinfo['id_type'] ==1 && $userinfo['idcardpassed'] == 1 && $flag) {
            $ret['code'] = '0';
            $ret['msg'] = '已经实名认证';
            $ret['mobile_code'] = $mobile_code;
            \es_session::set('DoForgetPwd_idno_verify', '1');//如果身份认证过，则设置标志位，以防止跳过身份验证
            $this->show_success($ret,'',1,0,'/user/Forgetpwdidno');
        } else {
            $ret['code'] = '3';
            $ret['msg'] = '没有进行实名认证';
            $ret['mobile_code'] = $mobile_code;
            \es_session::set('DoForgetPwd_idno_verify', 'no_idno');
            $this->show_error($ret,'',1,0,'/user/Resetpwd');
        }
        return;
    }
}
