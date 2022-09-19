<?php

namespace web\controllers\enterprise;

use libs\web\Form;
use web\controllers\BaseAction;

/**
 * 企业用户找回密码
 */
class CheckMajorCondentials extends BaseAction {
    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'major_condentials_no' => array('filter' => 'required', 'message' => '代理人证件号为空'),
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

        $ret = array();
        $userId = \es_session::get('DoForgetPwdP2p_uid_1');
        $userinfo = $this->rpc->local('EnterpriseService\getInfo', array($userId));
        if ($data['major_condentials_no'] !== $userinfo['contact']['major_condentials_no']) {
            $ret['code'] = '3';
            $ret['msg'] = '代理人证件号输入错误';
            $this->show_error($ret, '', 1);
            return false;
        }

        $ret['code'] = '0';
        $ret['msg'] = '用户验证成功';
        return $this->show_success($ret, '', 1);
    }
}