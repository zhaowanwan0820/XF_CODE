<?php
/**
 * 获取手机验证码 已登录用户
 * @author 长路<pengchanglu@ucfgroup.com>
 * 
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Block;
use core\dao\MobileVcodeModel;


class EMCode extends BaseAction {
    
    
	public function init() {
	}

    
	public function invoke(){
        $form = new Form('post');
        $form->rules = array(
            'isrsms' => array('filter' => 'int','message' => '参数错误'),
            'is_edit' => array('filter' => 'int','message' => '参数错误'),
            'is_delivery' => array('filter' => 'int','message' => '参数错误'),
            'is_weblogin' => array('filter' => 'int','message' => '参数错误'),
            'mobile' => array('filter' => 'int','message' => '参数错误'),
        );
        $form->validate();

       $isrsms = false;
        //已登录用户修改手机号码
       $is_edit = $form->data['is_edit'];
       if($is_edit){
           $user_id = intval ( $GLOBALS['user_info']['id'] );
           if($user_id){
               $user_info = $this->rpc->local('UserService\getUser', array($user_id));
               $is_send = $this->rpc->local('MobileCodeService\isSend',array($user_info['mobile'],2));
               if ($is_send != 1){
                   $error_msg = $this->rpc->local('MobileCodeService\getError',array($is_send));
                   echo json_encode($error_msg);
                   return;
               }
               $country_code = empty($user_info['country_code'])? 'cn': $user_info['country_code'];
               if (isset($_POST['req']) && $_POST['req']=='resetbankcard')
               {
                    $rs = $this->rpc->local('MobileCodeService\sendVerifyCode',array($user_info['mobile'],1,$isrsms,15,$country_code));
               }
               else {
                    $rs = $this->rpc->local('MobileCodeService\sendVerifyCode',array($user_info['mobile'],1,$isrsms,3,$country_code));
               }
               return;
           }
       }

       //修改或设置收获地址，短信发送
       $is_delivery = $form->data['is_delivery'];
       if($is_delivery){
           $user_id = intval ( $GLOBALS['user_info']['id'] );
           if($user_id){
               $user_info = $this->rpc->local('UserService\getUser', array($user_id));
               $is_send = $this->rpc->local('MobileCodeService\isSend',array($user_info['mobile'],2));
               if ($is_send != 1){
                   $error_msg = $this->rpc->local('MobileCodeService\getError',array($is_send));
                   echo json_encode($error_msg);
                   return;
               }
               $country_code = empty($user_info['country_code'])? 'cn': $user_info['country_code'];
               $rs = $this->rpc->local('MobileCodeService\sendVerifyCode',array($user_info['mobile'],1,$isrsms,$is_delivery,$country_code));
               return;
           }
       }

       //用户登录身份验证,短信发送$is_weblogin=9
       $is_weblogin = $form->data['is_weblogin'];
       $user_info['mobile']=$form->data['mobile'];
       if($is_weblogin){
               //$user_info = $this->rpc->local('UserService\getUser', array($user_id));
               $is_send = $this->rpc->local('MobileCodeService\isSend',array($user_info['mobile'],2));
               if ($is_send != 1){
                   $error_msg = $this->rpc->local('MobileCodeService\getError',array($is_send));
                   echo json_encode($error_msg);
                   return;
               }
               $rs = $this->rpc->local('MobileCodeService\sendVerifyCode',array($user_info['mobile'],1,$isrsms,$is_weblogin));
               return;
       }

    }
}
