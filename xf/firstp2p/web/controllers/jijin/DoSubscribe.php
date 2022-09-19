<?php
/**
 * 接收预约
 * @author 杨庆<yangqing@ucfgroup.com>
 **/

namespace web\controllers\jijin;

use libs\web\Form;
use web\controllers\BaseAction;

class DoSubscribe extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "fund_id" => array("filter"=>"int",'message'=>'参数错误'),
            "realname" => array("filter"=>"string",'message'=>'参数错误'),
            'phone' => array('filter' => 'reg', "message" =>'手机号格式不正确',"option" => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/")),
            "money" => array("filter"=>"string",'message'=>'参数错误'),
            "comment" => array("filter"=>"length",'message'=>'备注不能超过500个字','option'=>array('max'=>500)),
            'token' => array('filter' => 'required', 'message' => '表单过期'),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $ajax = 1;
        // 验证表单令牌
        if(!check_token()){
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", $ajax);
        }
        if($data['money']<=0){
            return $this->show_error('预约金额不正确', "", 1);
        }
        $user = $GLOBALS['user_info'];
        if($user['idcardpassed'] != 1){
            return $this->show_error('请先进行身份认证。', "", 1);
        }

        $userid = $user['id'];

        $platform = 1;//PC端
        $ret = $this->rpc->local('FundSubscribeService\add', array($data['fund_id'],$userid,$data['phone'],$data['money'],$data['comment'],$platform));
        if($ret['code']){
            return $this->show_success('预约成功', "", 1);
        }else{
            return $this->show_error($ret['msg'], "", 1);
        }

    }

}
