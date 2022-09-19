<?php
/**
 * 企业用户重置密码成功页
 * Created by PhpStorm.
 * User: yinli
 * Date: 2018/6/12
 * Time: 18:44
 */

namespace web\controllers\enterprise;

use libs\web\Form;
use web\controllers\BaseAction;


class ForgetSuc extends BaseAction {
    public function init(){
    }

    public function invoke() {
        $data = $this->form->data;
        $userId = \es_session::get('DoForgetPwdP2p_uid_3');
        if (empty($userId)) {
            $step = \es_session::get('DoForgetPwdP2p_step');
            $step = $step ?: '/enterprise/forgetPwd';
            return $this->show_error('先设置新密码', '错误', 0, 0, $step);
        }
    }
}