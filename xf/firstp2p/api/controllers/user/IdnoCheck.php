<?php
namespace api\controllers\user;

use api\conf\Error;
use api\controllers\FundBaseAction;
use libs\web\Form;

/**
 * IdnoCheck
 * @abstract  检查身份证是否存在
 * @author zhaohui3 <zhaohui3@ucfgroup.com>
 * @date 2015-06-18
 */
class IdnoCheck extends FundBaseAction
{
   public function init() 
   {
       parent::init();
       $this->form=new Form('post');
       $this->form->rules=array(
               'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
               'idno'=>array('filter'=>"required",'message'=>'身份证号不能为空！')
       );
       if (!$this->form->validate()) {
           $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
           return false;
       }
   }
   public function invoke() 
   {
       $data = $this->form->data;
       
       if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", trim($data['idno']))) {
           $msg = '身份认证号码不符合要求';
           $this->setErr('ERR_MANUAL_REASON', $msg);
           return false;
       }
       $result = $this->rpc->local('UserService\isIdCardExist', array($data['idno']));
       if ($result) {
           $userinf=$this->rpc->local('UserService\getUserByIdno', array($data['idno'])); 
           $res[isIdnoExist]=true;
           $res[userId]=$userinf[id];
       } else {
           $res[isIdnoExist]=false;
       }   
       $this->json_data=$res;
       return true;
   }
}