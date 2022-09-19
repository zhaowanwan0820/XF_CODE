<?php

/**
 * 用户登录变现通投资意向
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\LoanIntentionService;


class DoLoanIntention extends BaseAction {


    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'captcha' => array('filter' => 'string'),
            'money' => array("filter" => "reg", "option" => array("regexp" => '/^\d{4,7}$/'), "message" => "借款金额格式错误"),
            "time"   => array("filter" => "reg", "option" => array("regexp" => '/^\d{1,2}$/'), "message" => "借款期限格式错误"),
            "phone"  => array("filter" => "reg", "option" => array("regexp" => '/^\d{11}$/'), "message" => "联系电话格式错误"),
            'addr' => array('filter' => 'string'),
            'agreement' => array('filter' => 'int'),
            'code' => array('filter' => 'string'),
            'company' => array('filter' => 'string'),
            'wl' => array('filter'=>'string'),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
        }
    }

    public function invoke() {

        if( $this->form->data['agreement'] != 1 ){
            echo json_encode( array('errno'=>1,'errmsg'=>'表单填写有误，请重新填写') );
            return false;
        }

        $checkRet = $this->rpc->local('LoanIntentionService\checkQualification',array($GLOBALS['user_info'],trim($this->form->data['code'])));
        if( $checkRet['errno'] !== 0 ){
            echo json_encode( array('errno'=>1,'errmsg'=>'申请人条件不符合或者邀请码有误，请重新填写') );
            return false;
        }

        $type = $checkRet['ext']['type'];
        $this->form->data['type'] = $type;
        // 先检查是校验验证码
        $verify = \es_session::get('verify');
        $captcha = $this->form->data['captcha'];
        if (md5($captcha) !== $verify) {
            $this->_error = '您输入的验证码错误 ';
            echo json_encode( array('errno'=>2,'errmsg'=>'验证码有误，请重试') );
            return false;
        }
        if ( $this->form->data['time']<=0 || $this->form->data['time']>36 ){
            echo json_encode( array('errno'=>3,'errmsg'=>'表单填写有误，请重新填写') );
            return false;
        }
        $addNewApplyRet = $this->rpc->local('LoanIntentionService\addNewIntention',array($GLOBALS['user_info'],$this->form->data));
        if( $addNewApplyRet['errno'] !== 0 ){
            if($addNewApplyRet['errno'] == 5){
                echo json_encode( array('errno'=>4,'errmsg'=>'申请已经提交，请勿重复申请') );
            }elseif($addNewApplyRet['errno'] == 4){
                echo json_encode( array('errno'=>4,'errmsg'=>'借款金额必须为1000的整数倍') );
            }else{
                echo json_encode( array('errno'=>4,'errmsg'=>'申请提交有误，请稍后重试') );
            }
            return false;
        }
        //session 传递类型
        \es_session::set('loanIntention',$type);
        echo json_encode( array('errno'=>0,'errmsg'=>'成功') );
        return true;
    }
}
