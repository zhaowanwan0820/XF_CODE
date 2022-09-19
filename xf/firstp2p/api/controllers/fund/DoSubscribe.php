<?php
/**
 * 接收预约
 * @author 杨庆<yangqing@ucfgroup.com>
 **/

namespace api\controllers\fund;

use libs\web\Form;
use api\controllers\AppBaseAction;

class DoSubscribe extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "fund_id" => array("filter"=>"int",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
            "realname" => array("filter"=>"string",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
            'phone' => array('filter' => 'reg', "message" =>'ERR_SIGNUP_PARAM_PHONE',"option" => array("regexp" => "/^1[3456789]\d{9}$/")),
            "money" => array("filter"=>"string",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
            "comment" => array("filter"=>"length",'message'=>'ERR_FUND_SUB_COMMENT_FAIL','option'=>array('max'=>500)),
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        if($data['money']<=0){
            $this->setErr('ERR_MONEY_FORMAT');
            return false;
        }

        $res = $this->rpc->local("UserService\getUserByCode", array($this->form->data['token']));
        if($res['code']){
            $this->setErr('ERR_GET_USER_FAIL');
            return;
        }
        $user = $res['user'];

        if($user['idcardpassed'] != 1){
            $this->setErr('ERR_IDENTITY_NO_VERIFY', '请先进行身份认证。');
            return false;
        }

        $userid = $user['id'];
        $platform = 0;
        if (stripos($_SERVER['HTTP_OS'], 'Android') !== false) {
            $platform = 2;
        } elseif (stripos($_SERVER['HTTP_OS'], 'iOS') !== false) {
            $platform = 3;
        }
        $ret = $this->rpc->local('FundSubscribeService\add', array($data['fund_id'],$userid,$data['phone'],$data['money'],$data['comment'],$platform));
        if($ret['code']){
            $this->json_data = $data;
        }else{
            $this->setErr('ERR_SYSTEM',$ret['msg']);
            return false;
        }

    }

}
