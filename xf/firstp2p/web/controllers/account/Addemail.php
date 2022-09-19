<?php

/**
 *   添加邮箱
 *  @author xiaoan<zhaoxiaoan@ucfgroup.com>
 */
namespace web\controllers\account;

use core\service\MsgConfigService;
use web\controllers\BaseAction;
use libs\web\Form;

class Addemail extends BaseAction {

    private $_error = null;

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
        $act  = ($site_id == 1) ? '/account/setup' : '/account';
        if (!empty($GLOBALS ['user_info']['email'])){
            return $this->show_error('您已经设置了邮箱','系统提示', 0, 0, $act);
        }
        // 邮件订阅选项
        $user_set_service = new MsgConfigService();
        $email_config_list = MsgConfigService::$email_config;
        $user_config_email_info = $user_set_service->getUserConfig($GLOBALS ['user_info']['id'], 'email_switches');
        $email_default_config = MsgConfigService::$default_checked;
        if ($_POST) {
            // 验证表单令牌
            if(!check_token()) {
                return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", 0,0,'/account/addemail');
            }
            $this->form = new Form("post");
            $this->form->rules = array(
                'email' => array(
                    'filter' => 'email','message'=>'邮箱格式错误'
                ),
            );
            if (! $this->form->validate()) {
                $this->_error = $this->form->getErrorMsg();
            }

            if (!empty($this->error)){
                return $this->show_error($this->_error,'', 0, 0, '/account/addemail');
            }

            $data = $this->form->data;
            $data['re_email'] = trim($_POST['re_email']);
            $data['email_config'] = $_POST['email_config'];

            if ($data['email']!=$data['re_email']){
                return $this->show_error('两次输入的邮箱不一致','', 0, 0, '/account/addemail');
            }

            // check email_config
            $result_check_email_config = $user_set_service->checkMsgConfig( $data['email_config'],MsgConfigService::TYPE_EMAIL);
            if ($result_check_email_config == false){
                return $this->show_error('邮件订阅选项错误，请重新选择','', 0, 0, '/account/addemail');
            }
            $user_id = intval($GLOBALS['user_info']['id']);
            $user_info = $this->rpc->local('UserService\getUser', array($user_id));

            if(empty($user_info)){
                return $this->show_error('用户不存在');
            }
            $is_exist = $this->rpc->local('UserService\checkEmailExist', array($data['email']));
            if($is_exist){
                return $this->show_error('该邮箱已被使用','', 0, 0, '/account/addemail');
            }

            $save_data = array('id' => $user_id, 'email' => $data['email']);
            $save = $this->rpc->local('UserService\updateInfo', array($save_data));
            if($save){
                // 邮件订阅设置
                $user_set_service = new MsgConfigService();

                $ret = $user_set_service->setSwitches($user_id, 'email_switches', $data['email_config']);
                if (!$ret){
                    return $this->show_error('邮件订阅修改失败，请稍后重试','', 0, 0, '/account/setmessage');
                }
                return $this->show_success('设置邮箱成功！', '', 0, 0, $act);
            }else{
                return $this->show_success('设置邮箱失败，稍后重试！', '', 0, 0, '/account/addemail');
            }

        }


        $this->tpl->assign('user_config_email_info',$user_config_email_info);
        $this->tpl->assign('email_default_config',$email_default_config);
        $this->tpl->assign('email_config_list',$email_config_list);
    }

}
