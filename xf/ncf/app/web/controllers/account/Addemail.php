<?php

/**
 *   添加邮箱
 *  @author xiaoan<zhaoxiaoan@ucfgroup.com>
 */
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\MsgConfigService;
use core\service\user\UserService;

class Addemail extends BaseAction {

    private $_error = null;

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
        $act  = ($site_id == 1) ? '/account/setup' : '/account';
        if (!empty($GLOBALS['user_info']['email'])){
            return $this->show_error('您已经设置了邮箱','系统提示', 0, 0, $act);
        }
        // 用户ID
        $user_id = isset($GLOBALS['user_info']['id']) ? intval($GLOBALS['user_info']['id']) : 0;
        // 获取用户订阅消息配置
        $msgAllConfig = MsgConfigService::getAllMsgConfig();
        // 邮件订阅选项
        $email_config_list = $msgAllConfig['email_config'];
        $user_config_email_info = MsgConfigService::getUserConfig($user_id, 'email_switches');
        $email_default_config = $msgAllConfig['default_checked'];
        if ($_POST) {
            // 验证表单令牌
            if (!check_token()) {
                return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", 0, 0, '/account/addemail');
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

            if (!empty($this->error)) {
                return $this->show_error($this->_error,'', 0, 0, '/account/addemail');
            }

            $data = $this->form->data;
            $data['re_email'] = trim($_POST['re_email']);
            $data['email_config'] = $_POST['email_config'];

            if ($data['email'] != $data['re_email']){
                return $this->show_error('两次输入的邮箱不一致','', 0, 0, '/account/addemail');
            }

            // check email_config
            $result_check_email_config = MsgConfigService::checkMsgConfig($data['email_config'], $msgAllConfig['TYPE_EMAIL']);
            if ($result_check_email_config == false) {
                return $this->show_error('邮件订阅选项错误，请重新选择','', 0, 0, '/account/addemail');
            }

            // 更新用户邮箱
            $ret = UserService::updateUserEmail($user_id, $data['email']);
            if (!isset($ret) || $ret['code'] == -2) {
                return $this->show_error('用户不存在');
            }
            if ($ret['code'] == -3) {
                return $this->show_error('该邮箱已被使用','', 0, 0, '/account/addemail');
            }

            if ($ret['code'] == 0) {
                // 邮件订阅设置
                $ret = MsgConfigService::setSwitches($user_id, 'email_switches', $data['email_config']);
                if (!$ret) {
                    return $this->show_error('邮件订阅修改失败，请稍后重试|$ret:'.json_encode($ret),'', 0, 0, '/account/setmessage');
                }
                return $this->show_success('设置邮箱成功！', '', 0, 0, $act);
            }else{
                return $this->show_success('设置邮箱失败，稍后重试！', '', 0, 0, '/account/addemail');
            }
        }
        $this->tpl->assign('user_config_email_info', $user_config_email_info);
        $this->tpl->assign('email_default_config', $email_default_config);
        $this->tpl->assign('email_config_list', $email_config_list);
    }
}