<?php

/**
 * 忘记密码身份证号页面
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use libs\utils\Block;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
class DoForgetPwdIdno extends BaseAction {

    private $_error = null;

    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'idno' => array(
                    'filter' => 'required',
                    'message' => '身份证号不能为空'
            ),
            'ajax' => array('filter' => 'string')//1：异步校验身份证号
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
            $ret['code'] = '2';
            $ret['msg'] = $this->_error['idno'];
            $ret['data'] = $this->form->data;
            $this->show_error($ret,'',1,0,'/user/forgetpwdidno');
            return false;
        }
    }

    public function invoke() {
        //code:'-1':令牌错误 ，0:请求成功,身份证号正确，  1：请重新输入身份证号，2：身份证账号不能为空
        // 验证表单令牌
        $data = $this->form->data;
        if($data['ajax'] !='1' && !check_token()) {
            return $this->error();
        }
        $data['phone'] = \es_session::get('DoForgetPwd_phone');//取出手机号，验证身份证号
        $userinfo = $this->rpc->local('UserService\getByMobile', array($data['phone'],'id,idcardpassed,idno'));
        RiskServiceFactory::instance(Risk::BC_CHANGE_PWD)->check($userinfo,Risk::ASYNC,$data);
        $idno_check_hours = Block::check('MODIFYPWD_CHECK_IDNO_HOURS',$userinfo['id'],true);//先验证身份证号输入错误频率是否已经超过限制
        if ($idno_check_hours === false) {
            \es_session::set('DoModifyPwd_idno_error', '错误次数过多,请稍后重试');//如果身份证号错误次数过多则在频率限制内禁止修改密码
            return $this->error();
        }
        if (!$data['phone']) {
            $ret['code'] = '-1';
            $ret['msg'] = 'error_jump';
            return $this->show_error($ret, '', 1, 0,'/user/ForgetPwd');
        }
        setlog(array('uid'=>$userinfo['id']));
        if (empty($data['idno']) || $data['idno'] != $userinfo['idno']){
            $idno_check_hours = Block::check('MODIFYPWD_CHECK_IDNO_HOURS',$userinfo['id'],false);//身份证号输入错误频率限制
            if ($idno_check_hours === false) {
                \es_session::set('DoModifyPwd_idno_error', '错误次数过多,请稍后重试');//如果身份证号错误次数过多则在频率限制内禁止修改密码
                return $this->error();
            }
            $ret['code'] = '1';
            $ret['msg'] = '输入身份证号不正确';
            $this->show_error($ret,'',1,0);
            return false;
        } else {
            \es_session::set('DoForgetPwd', '2');//如果身份证号码正确
            \es_session::set('DoForgetPwd_idno', $data['idno']);//如果身份证号码正确
            $ret['code'] = '0';
            $ret['msg'] = '身份证号正确';
            $this->show_success($ret,'',1,0,'/user/Resetpwd');
            RiskServiceFactory::instance(Risk::BC_CHANGE_PWD)->notify();
            return;
       }
    }
    function error()
    {
        $ret['code'] = '-1';
        $ret['msg'] = 'error_jump';
        return $this->show_error($ret,'',1,0,'/user/ForgetPwd');
    }
}
