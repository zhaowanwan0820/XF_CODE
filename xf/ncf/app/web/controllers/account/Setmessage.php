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
        $userId = isset($GLOBALS['user_info']['id']) ? (int)$GLOBALS['user_info']['id'] : 0;
        if (empty($userId)) {
            return $this->show_error('请登陆操作','', 0, 0, '/user/login');
        }

        // 获取用户订阅消息配置
        $msgAllConfig = MsgConfigService::getAllMsgConfig();

        // 普惠的“短信通知”设置
        // @todo 这里修改了原有的结构，下面在存储的时候，需要保证结构的完整
        $sms_config_list = array(
            0 => array(18=>'项目出借', 9=>'项目回款', 11=>'项目流标'),   // 项目进度
            1 => array(6=>'提现成功', 34=>'充值成功', 39=>'优惠券'),     // 充值/提现
            2 => array()                                               // 活动奖励
        );

        $email_config_list = $msgAllConfig['email_config'];

        $user_config_email_info = MsgConfigService::getUserConfig($userId, 'email_switches');
        $user_config_sms_info = MsgConfigService::getUserConfig($userId, 'sms_switches');
        $is_have_email = !empty($GLOBALS['user_info']['email']) ? 1 : 0;
        $sms_default_config = $msgAllConfig['default_checked'];
        $email_default_config = empty($is_have_email) ? 0 : $msgAllConfig['default_checked'];
        if ($_POST) {
            // 验证表单令牌
            if (!check_token()) {
                return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], '', 0, 0, '/account/setmessage');
            }

            // 提交表单
            if ($is_have_email == 0) {
                if (!empty($_POST['email_config']) && array_search('1', $_POST['email_config']) !=false) {
                    return $this->show_error('请先绑定邮箱', '', 0, 0, '/account/addemail');
                }
            }

            foreach ($msgAllConfig['sms_config'] as $item) {
                foreach ($item as $key=>$text) {
                    // 为了保证两边采用一致的结构，需要复原相关字段
                    if (!isset($_POST['sms_config'][$key])) {
                        // 获取用户已经存在的值
                        $_POST['sms_config'][$key] = isset($user_config_sms_info[$key]) 
                            ? $user_config_sms_info[$key] : $sms_default_config;
                    }
                }
            }

            $check_sms_config_form = MsgConfigService::checkMsgConfig($_POST['sms_config'], $msgAllConfig['TYPE_SMS']);
            if ($check_sms_config_form == false) {
                return $this->show_error('短信订阅选项错误，请重新选择', '', 0, 0, '/account/setmessage');
            }

            $check_email_config_form = MsgConfigService::checkMsgConfig($_POST['email_config'], $msgAllConfig['TYPE_EMAIL']);
            if ($check_email_config_form == false) {
                return $this->show_error('邮件订阅选项错误，请重新选择', '', 0, 0, '/account/setmessage');
            }

            // 保存设置
            $data = array(
                'sms_config_form' => $_POST['sms_config'],
                'email_config_form' => $_POST['email_config']
            );

            $sms_ret = MsgConfigService::setSwitches($userId, 'sms_switches', $data['sms_config_form']);
            if ($sms_ret) {
                $email_ret = MsgConfigService::setSwitches($userId, 'email_switches', $data['email_config_form']);
                if ($email_ret) {
                    return $this->show_success('消息设置成功', '', 0, 0, '/account/setmessage');
                } else {
                    return $this->show_error('邮件订阅设置失败');
                }
            } else {
                return $this->show_error('邮件订阅设置失败');
            }
        }

        $this->tpl->assign("sms_config_list", $sms_config_list);
        $this->tpl->assign("email_config_list", $email_config_list);
        $this->tpl->assign('user_config_email_info', $user_config_email_info);
        $this->tpl->assign('user_config_sms_info', $user_config_sms_info);
        $this->tpl->assign('sms_default_config', $sms_default_config);
        $this->tpl->assign('email_default_config', $email_default_config);
        $this->tpl->assign('is_have_email', $is_have_email);
        $this->tpl->assign("inc_file","web/views/account/setmessage.html");
        $this->template = "web/views/account/frame.html";
    }
}