<?php

namespace web\controllers\enterprise;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\UserModel;

/**
 * 企业用户找回密码
 */
class CheckPhone extends BaseAction {
    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'mobile' => array(
                'filter' => 'reg',
                "message" => "手机号码格式不正确",
                "option" => array("regexp" => "/^1[3456789]\d{9}$/")
            ),
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

    public function invoke() {
        $data = $this->form->data;

        $userId = \es_session::get('DoForgetPwdP2p_uid_1');
        $viewContent = $data['viewContent'];
        $userinfo = $this->rpc->local('EnterpriseService\getInfo', array($userId));
        if ($viewContent == 1) {
            //验证业务接洽联系方式是否正确
            if($data['mobile'] !== $userinfo['contact']['consignee_phone']) {
                $ret['code'] = '5';
                $ret['msg'] = '联系方式输入错误';
                $this->show_error($ret, '', 1);
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
        } elseif ($viewContent == 3) {
            //验证代理人手机号、证件号，法定代表人证件号是否正确
            if($data['mobile'] !== $userinfo['contact']['major_mobile']) {
                $ret['code'] = '5';
                $ret['msg'] = '代理人联系方式输入错误';
                $this->show_error($ret,'',1,0);
                return false;
            }
        }

        $ret['code'] = '0';
        $ret['msg'] = '用户验证成功';
        return $this->show_success($ret, '', 1);
    }
}