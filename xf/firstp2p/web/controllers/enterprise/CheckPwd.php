<?php

namespace web\controllers\enterprise;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;

/**
 * 企业用户找回密码
 */
class CheckPwd extends BaseAction {
    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'new_password' => array(
                'filter' => 'length',
                'message' => '新密码长度为6-20位',
                "option" => array(
                        "min" => 6,
                        "max" => 20
                )
            ),
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

        $userId = \es_session::get('DoForgetPwdP2p_uid_3');
        $data = $this->form->data;
        $bo = BOFactory::instance('web');
        $ret = $bo->verifyPwd($userId, $data['new_password']);
        if ($ret['code'] == '0') {
            $ret['msg'] = '新密码不能和旧密码相同';
            return $this->show_error($ret, '', 1, 0);
        }

        $ret['code'] = '0';
        $ret['msg'] = '用户验证成功';
        return $this->show_success($ret, '', 1);
    }
}