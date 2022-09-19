<?php

/**
 * 修改密码
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use libs\utils\Block;
use libs\utils\Logger;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
class DoModifyPwd extends BaseAction {

    private $_error = null;

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form('post');
        $this->form->rules = array(
            'old_password' => array(
                    'filter' => 'required',
                    'message' => '密码不能为空',
                    'option' => array("optional"=>true)
            ),
            'phone' => array(
                'filter' => 'reg',
                "message" => "手机号码格式不正确",
                "option" => array(
                    "regexp" => "/^1[3456789]\d{9}$/",
                    "optional"=>true
                )
            ),
            'captcha' => array('filter' => 'string'),
            'ajax' => array('filter' => 'required', 'message' => '参数错误'),//0：异步校验所有参数 ，1:异步校验old_password,2：异步校验phone
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
            if ($this->_error['old_password']) {
                $ret['code'] = '1';
                $ret['msg'] = $this->_error['old_password'];
            } elseif ($this->_error['phone']) {
                $ret['code'] = '2';
                $ret['msg'] = $this->_error['phone'];
            } elseif ($this->_error['ajax']) {
                $ret['code'] = '-2';
                $ret['msg'] = $this->_error['ajax'];
            }
            $this->show_error($ret,'',1,0,'/user/editpwd');
            return false;
        }
    }

    public function invoke() {
        //code:  -2:参数错误 ，-1:令牌错误，0：可以发送短信验证 ，1：密码不能为空，2：手机号码格式不正确,3:旧密码相关错误信息，4：验证手机号相关错误信息 ，5：图形验证码不正确

        // 验证表单令牌
        $data = $this->form->data;
        $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['code'];
        if($data['ajax'] == 0 && !check_token()) {
            return $this->error();
        }
        RiskServiceFactory::instance(Risk::BC_CHANGE_PWD)->check($GLOBALS['user_info'],Risk::ASYNC,$data);
        $user_id = intval($GLOBALS['user_info']['id']);
        $bo = BOFactory::instance('web');
        //验证旧密码
        if ($data['ajax'] == 1 || $data['ajax'] == 0) {
            $old_check_hours = Block::check('OLDPWD_CHECK_HOURS',$user_id,true);//旧密码验证前先验证输入错误频率限制
            if ($old_check_hours === false) {
                \es_session::set('DoModifyPwd_pwd_error', '错误次数过多,请稍后重试');//如果密码错误次数过多则在频率限制内禁止修改密码
                return $this->error();
            }
            $ret = $bo->verifyPwd($user_id,$data['old_password']);
            if ($ret['code'] == '3') {
                $old_check_hours = Block::check('OLDPWD_CHECK_HOURS',$user_id,false);//旧密码输入错误频率限制
                if ($old_check_hours === false) {
                    \es_session::set('DoModifyPwd_pwd_error', '错误次数过多,请稍后重试');//如果密码错误次数过多则在频率限制内禁止修改密码
                    return $this->error();
                }
                $this->show_error($ret,'',1,0,'/user/editpwd');
                return false;
            }
            if ($data['ajax'] == 1 && $ret['code'] == '0') {
                $ret['msg'] = '旧密码正确';
                $this->show_success($ret,'',1,0);
                RiskServiceFactory::instance(Risk::BC_CHANGE_PWD)->notify();
                return;
            }
        }

        //验证手机号码
        if ($data['ajax'] == 2 || $data['ajax'] == 0) {
            $ret = $bo->verifyPwd($user_id,'',$data['phone']);
            if ($ret['code'] == '4') {
                $this->show_error($ret,'',1,0,'/user/editpwd');
                return false;
            }
            if ($data['ajax'] == 2 && $ret['code'] == '0') {
                $ret['msg'] = '手机号正确';
                $this->show_success($ret,'',1,0);
                RiskServiceFactory::instance(Risk::BC_CHANGE_PWD)->notify();
                return;
            }
        }

        //校验验图形验证码
        if ($data['ajax'] == 0 && $data['captcha']) {
            $verify = \es_session::get('verify');
            if (!empty($verify)) {
                // 验证码校验失败，立刻将session中verify设置成非MD5值
                \es_session::set('verify','xxx removeVerify xxx');
                $captcha = $data['captcha'];
                if (md5($captcha) !== $verify) {
                    $ret['code'] = '5 ';
                    $ret['msg'] = '图形验证码不正确';
                    $this->show_error($ret,'',1,0,'/user/editpwd');
                    return false;
                }
                \es_session::set('DoModifyPwd', '1');//旧密码和手机号输入正确后，可以点击下一步发送短信验证
                $ret['code'] = '0';
                $ret['msg'] = '可以发送短信';
                $ret['mobile_code'] = $mobile_code;
                $this->show_success($ret,'',1,0,'/user/EMCode');
                RiskServiceFactory::instance(Risk::BC_CHANGE_PWD)->notify();
                return;
            } else {
                $ret['code'] = '5 ';
                $ret['msg'] = '图形验证码不正确';
                $this->show_error($ret,'',1,0,'/user/editpwd');
                return false;
            }
        }
    }
    function error()
    {
        $ret['code'] = '-1';
        $ret['msg'] = 'error_jump';
        return $this->show_error($ret,'',1,0,'/user/editpwd');
    }
}
