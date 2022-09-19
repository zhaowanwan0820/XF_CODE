<?php

namespace web\controllers\bind;

use libs\web\Form;
use libs\web\Bind;
use \libs\utils\Logger;
use web\controllers\BaseAction;
use core\service\MobileCodeService;

class Done extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'code'   => array('filter' => 'string'),
            'mobile' => array('filter' => 'string'),
            'type'   => array('filter' => 'string'),
        );

        $this->form->validate();
    }

    public function invoke() {
        $userBind = \es_session::get("user_bind");
        if (!$userBind) {  //非法进入
            Logger::error("会话已经过期, 请重新授权登录, data:" . json_encode($this->form->data));
            return ajax_return(dataPack(1, '会话已经过期, 请重新授权登录'));
        }

        $data   = \es_session::get('bind_data'); //要绑定的数据
        $ucode  = $this->form->data['code'];
        $mobile = $data['checkMobile'];
        if (empty($mobile)) { //外部填的, 第三方可能没有手机号
            $mobile = $this->form->data['mobile'];
            $data['openBindData']['thirdUserInfo']['mobile'] = $mobile;
        }

        if ('login' == $this->form->data['type']) {
            return $this->dealLoginUser($data);
        }

        if ($data['isUserBind']) {
            return $this->dealBindUser($data, $mobile, $ucode); //验号, 登录
        }

        if ($data['isp2pUser']) { //绑定
            return $this->dealExistsUser($data, $mobile, $ucode);
        }

        return $this->dealNewCreate($data, $mobile, $ucode); //验号、注册、绑定、登录
    }

    private function dealLoginUser($data) {
        if ($GLOBALS['user_info']['id'] != $data['p2pUserId']) {
            Logger::error("授权绑定失败, 期待登录者{$data['p2pUserId']}, 登录者: " . json_encode($GLOBALS['user_info']));
            $this->tpl->assign('errmsg', '绑定失败! 请使用与身份证同一用户帐号登录');
            $this->template = 'web/views/v3/bind/error.html';
            return false;
        }

        $data['p2pUserId'] = $GLOBALS['user_info']['id'];
        $bindRes = Bind::saveOpenBind($data);
        if ($bindRes['code']) {
            Logger::error("授权绑定失败, 输入:" . json_encode($data) . " , 输出: " . json_encode($bindRes));
            $this->tpl->assign('errmsg', '用户授权登录失败!');
            $this->template = 'web/views/v3/bind/error.html';
            return false;
        }

        Bind::setBindSign($data['cookBindSign']);
        $clientToken = $data['openBindData']['userParam']['params']['client_token'];
        \es_session::set('pass_client_token', $clientToken);

        $url = Bind::getJumpUrl($data);
        header('location:' . $url);

        return true;
    }

    private function dealNewCreate($data, $mobile, $ucode) {
        if (!$this->checkCode($mobile, $ucode)) {
           Logger::error("验证码错误, mobile:{$mobile}, code:{$ucode}");
           return ajax_return(dataPack(1, '验证码错误'));
        }

        $createRes = Bind::createUser($data);
        if ($createRes['code']) {
            Logger::error("创建用户失败, 输入:" . json_encode($data) . " , 输出: " . json_encode($createRes));
            return 2 == $createRes['code'] ? ajax_return(dataPack(2, '手机号码已经被占用')) :  ajax_return(dataPack(1, '创建登录用户失败'));
        }

        $data['p2pUserId'] = $createRes['data']['user_id'];
        $bindRes = Bind::saveOpenBind($data);
        if ($bindRes['code']) {
              Logger::error("授权绑定失败, 输入:" . json_encode($data) . " , 输出: " . json_encode($bindRes));
              return ajax_return(dataPack(1, '授权绑定失败'));
        }

        $sessRes = Bind::createSession($data);
        if ($sessRes['code']) {
            Logger::error("创建登录会话失败, 输入:" . json_encode($data) . " , 输出: " . json_encode($sessRes));
            return ajax_return(dataPack(1, '创建登录会话失败'));
        }

        $data['sess_data'] = $sessRes['data'];
        $return_jump['jump'] = !empty($bindRes['data']['url'])?$bindRes['data']['url']:Bind::getJumpUrl($data);
        return ajax_return(dataPack(0, '', $return_jump));
    }

    private function dealExistsUser($data, $mobile, $ucode) {
        if (!$this->checkCode($mobile, $ucode)) {
           Logger::error("验证码错误, mobile:{$mobile}, code:{$ucode}");
           return ajax_return(dataPack(1, '验证码错误'));
        }

        $bindRes = Bind::saveOpenBind($data);
        if ($bindRes['code']) {
              Logger::error("授权绑定失败, 输入:" . json_encode($data) . " , 输出: " . json_encode($bindRes));
              return ajax_return(dataPack(1, '授权绑定失败'));
        }

        $sessRes = Bind::createSession($data);
        if ($sessRes['code']) {
            Logger::error("创建登录会话失败, 输入:" . json_encode($data) . " , 输出: " . json_encode($sessRes));
            return ajax_return(dataPack(1, '创建登录会话失败'));
        }

        $data['sess_data'] = $sessRes['data'];
        $return_jump['jump'] = !empty($bindRes['data']['url'])?$bindRes['data']['url']:Bind::getJumpUrl($data);
        return ajax_return(dataPack(0, '', $return_jump));
    }

    private function dealBindUser($data, $mobile, $ucode) {
        if (!$this->checkCode($mobile, $ucode)) {
            Logger::error("验证码错误, mobile:{$mobile}, code:{$ucode}");
            return ajax_return(dataPack(1, '验证码错误'));
        }

        $sessRes = Bind::createSession($data);
        if ($sessRes['code']) {
            Logger::error("创建登录会话失败, 输入:" . json_encode($data) . " , 输出: " . json_encode($sessRes));
            return ajax_return(dataPack(1, '创建登录会话失败'));
        }

        $data['sess_data'] = $sessRes['data'];
        return ajax_return(dataPack(0, '', array('jump' => Bind::getJumpUrl($data))));
    }

    private function checkCode($mobile, $ucode) {
        if (!preg_match('~^\d{11}$~', $mobile) || !preg_match('~^\d{6}$~', $ucode)) {
            return false;
        }

        $serviceObj = new MobileCodeService();
        $scode = $serviceObj->getMobilePhoneTimeVcode($mobile);
        return $scode == $ucode;
    }

}
