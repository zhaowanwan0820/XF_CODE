<?php

/**
 *   消息订阅设置
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\MsgConfigService;

class Setmessage extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        if (empty($GLOBALS ['user_info']['id'])){
            return $this->show_error('请登陆操作','', 0, 0, '/user/login');
        }
        $user_set_service = new MsgConfigService();
        $sms_config_list = MsgConfigService::$sms_config;
        $email_config_list = MsgConfigService::$email_config;
        $user_config_email_info = $user_set_service->getUserConfig($GLOBALS ['user_info']['id'], 'email_switches');
        $user_config_sms_info = $user_set_service->getUserConfig($GLOBALS ['user_info']['id'], 'sms_switches');
        $is_have_email = !empty($GLOBALS ['user_info']['email']) ? 1 : 0;
        $sms_default_config = MsgConfigService::$default_checked;
        $email_default_config = empty($is_have_email) ? 0 : MsgConfigService::$default_checked;
        if ($_POST){
            // 验证表单令牌
            if(!check_token()) {
                return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", 0,0,'/account/setmessage');
            }
            // 提交表单
            if ($is_have_email == 0){
                if (!empty($_POST['email_config']) && array_search('1',$_POST['email_config']) !=false){
                    return $this->show_error('请先绑定邮箱', '', 0, 0, '/account/addemail');
                }
            }
            $check_sms_config_form = $user_set_service->checkMsgConfig($_POST['sms_config'],MsgConfigService::TYPE_SMS);
            if ($check_sms_config_form == false){
                return $this->show_error('短信订阅选项错误，请重新选择', '', 0, 0, '/account/setmessage');
            }
            $check_email_config_form = $user_set_service->checkMsgConfig($_POST['email_config'],MsgConfigService::TYPE_EMAIL);
            if ($check_email_config_form == false){
                return $this->show_error('邮件订阅选项错误，请重新选择', '', 0, 0, '/account/setmessage');
            }

            // 保存设置
            $data = array(
                'sms_config_form' => $_POST['sms_config'],
                'email_config_form' => $_POST['email_config']
            );
            $sms_ret = $user_set_service->setSwitches($GLOBALS ['user_info']['id'],'sms_switches',$data['sms_config_form']);
            if ($sms_ret){
                $email_ret = $user_set_service->setSwitches($GLOBALS ['user_info']['id'],'email_switches',$data['email_config_form']);
                if ($email_ret){
                    return $this->show_success('消息设置成功','',0,0, '/account/setmessage');
                }else{
                    return $this->show_error('邮件订阅设置失败');
                }
            }else{
                return $this->show_error('邮件订阅设置失败');
            }


        }


        $this->tpl->assign("sms_config_list", $sms_config_list);
        $this->tpl->assign("email_config_list", $email_config_list);
        $this->tpl->assign('user_config_email_info',$user_config_email_info);
        $this->tpl->assign('user_config_sms_info',$user_config_sms_info);
        $this->tpl->assign('sms_default_config',$sms_default_config);
        $this->tpl->assign('email_default_config',$email_default_config);
        $this->tpl->assign('is_have_email', $is_have_email);
        $this->tpl->assign("inc_file","web/views/v2/account/setmessage.html");

        $this->template = "web/views/v2/account/frame.html";
    }

}
