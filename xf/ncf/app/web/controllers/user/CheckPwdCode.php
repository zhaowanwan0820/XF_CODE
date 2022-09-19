<?php

/**
 * 修改密码
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\sms\MobileCodeService;

class CheckPwdCode extends BaseAction {

    private $_error = null;

    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'from' => array('filter' => 'string'),//modify_pwd,forget_pwd
            'code' => array('filter' => 'string'),
            'phone' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $mobileCodeObj = new MobileCodeService();
        $vcode = $mobileCodeObj->getMobilePhoneTimeVcode($data['phone']);

        if (!$this->form->data['code'] || $vcode != $this->form->data['code']) {
            $ret = array(
                'code' => '0',
                'msg' => '验证码不正确'
            );

            $this->show_error($ret,'',1,0,'/user/editpwd');
            return false;
        } else {
            $ret = array(
                'code' => '1',
                'msg' => '验证码正确'
            );
            // 删除短信验证码
            $mobileCodeObj->delMobileCode($data['phone']);
            if ($data['from'] == 'modify_pwd') {
                if (\es_session::get('DoModifyPwd') == '1') {
                \es_session::set('DoModifyPwd', '2');//短信验证正确后，可以进入修改密码下一个界面，否则不能进入下一个修改密码界面，防止绕过短信验证
                }
                $this->show_success($ret,'',1,0,'/user/Renewpwd');
            }
            if ($data['from'] == 'forget_pwd') {
                \es_session::set('DoForgetPwd', '1');//短信验证正确后，可以点击下一步进行表单提交
                \es_session::set('DoForgetPwd_phone', $data['phone']);//如果身份认证过，则设置标志位，以防止跳过身份验证
                $this->show_success($ret,'',1,0,'/user/Resetpwd');
            }
            return false;
        }
    }
}