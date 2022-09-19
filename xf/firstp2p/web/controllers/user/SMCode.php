<?php
/**
 * 设置或修改收货地址：
 * 获取手机验证码 已登录用户
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2015年7月23日
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
            'is_sendmobile' => array('filter' => 'int','message' => '参数错误'),//是否返回用户手机号码,‘1’则返回，其他不返回
            'isrsms' => array('filter' => 'int','message' => '参数错误'),
            'is_edit' => array('filter' => 'int','message' => '参数错误'),
        );
        $form->validate();

       $isrsms = false;
        //已登录用户修改手机号码
       $is_edit = $form->data['is_edit'];
       $is_sendmobile=$form->data['is_sendmobile'];
       if($is_sendmobile==1) {
           $user_id = intval ( $GLOBALS['user_info']['id'] );
           $user_info = $this->rpc->local('UserService\getUser', array($user_id));
           $user_mobile=substr_replace($user_info['mobile'],"****",3,4);
           return $user_mobile;
       }
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
               $rs = $this->rpc->local('MobileCodeService\sendVerifyCode',array($user_info['mobile'],1,$isrsms,3));
               $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($user_info['mobile']));
               return $vcode;
           }
       }
    }
}
