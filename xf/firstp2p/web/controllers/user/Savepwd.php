<?php
/**
 * 修改密码
 * @author wenyanlei@ucfgroup.com
 */
namespace web\controllers\user;
use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

class Savepwd extends BaseAction
{

    private $_error = null;

    public function init ()
    {
        if(!$this->check_login()) return false;
        $this->form = new Form('post');
        $this->form->rules = array(
                'old_password' => array(
                        'filter' => 'length',
                        'message' => '密码错误，请重新输入',
                        "option" => array(
                                "min" => 5,
                                "max" => 25
                        )
                ),
                'new_password' => array(
                        'filter' => 'length',
                        'message' => '新密码长度为6-20位',
                        "option" => array(
                                "min" => 6,
                                "max" => 20
                        )
                ),
                're_new_password' => array(
                        'filter' => 'length',
                        'message' => '确认密码长度为6-20位',
                        "option" => array(
                                "min" => 6,
                                "max" => 20
                        )
                )
        );
        if (! $this->form->validate()) {
            $this->_error = $this->form->getError();
        }
    }
    public function invoke ()
    {
        // 验证表单令牌
        if(!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", 0, 0,url('user/editpwd'));
        }
        if (! empty($this->_error)) {
            return $this->_show_error();
        }
        $bo = BOFactory::instance('web');
        $switch = app_conf('TURN_ON_FIRSTLOGIN');
        if ($switch == 3) {
            $data = $this->form->data;
            if ($data['old_password'] === $data['new_password']) {
                $this->_error = array(
                        'new_password' => '新密码和旧密码不能一致'
                );
                $this->_show_error();
            } elseif ($data['new_password'] !== $data['re_new_password']) {
                $this->_error = array(
                        're_new_password' => '新密码和确认密码不一致'
                );
                $this->_show_error();
            }
            //密码安全规则检查
            if ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1) {
                $len = strlen($this->form->data['new_password']);
                $mobile = $GLOBALS['user_info']['mobile'];
                $password = $this->form->data['new_password'];
                $password = stripslashes($password);
                \FP::import("libs.common.dict");
                $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
                $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
                if ($base_rule_result){
                    $this->_error = array('erro_msg' => $base_rule_result['errorMsg']);
                    return $this->_show_error();
                }
                //黑名单判断,禁用密码判断
                $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
                if ($forbid_black_result) {
                    $this->_error = array('erro_msg' => $forbid_black_result['errorMsg']);
                    return $this->_show_error();
                }
            }
            $user_id = intval($GLOBALS['user_info']['id']);
            $save = $bo->updatePwd($user_id, $data['old_password'],
                    $data['new_password'], $data['re_new_password']);
            if ($save['code'] == 0) {
                $this->_error = array(
                        'old_password' => $save['msg']
                );
                $this->_show_error();
            } else {
                // 增加短信提示
                if (app_conf("SMS_ON")==1){
                    // SMSSend 用户修改密码短信通知
                    if ($GLOBALS['user_info']['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                    {
                        $_mobile = 'enterprise';
                        $accountTitle = get_company_shortname($GLOBALS['user_info']['id']); // by fanjingwen
                    } else {
                        $_mobile = $GLOBALS['user_info']['mobile'];
                        $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                    }
                    $msg_content = array(
                        'account_title' => $accountTitle,
                        'modify_time' => date("m-d H:i"),
                    );

                    \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_SMS_MODIFY_PASSWORD_NEW', $msg_content, $user_id);
                }
                //生产用户访问日志
                UserAccessLogService::produceLog($user_id, UserAccessLogEnum::TYPE_UPDATE_PASSWORD, '修改密码成功', '', '', DeviceEnum::DEVICE_WEB);
                return $this->show_success('修改成功', '', 0, 0, url("account"));
            }
            } else {
                $this->_error = array(
                        'old_password' => '系统维护中'
                );
                $this->_show_error();
            }
            }

            private function _show_error ()
            {
                $this->tpl->assign('title', app_conf('SHOP_TITLE'));
                $this->tpl->assign("error", $this->_error);
                $this->tpl->assign("data", $this->form->data);
                //主站判断是不是企业账户，如果是企业账户，走以前的模板
                $user_mobile = $GLOBALS['user_info']['mobile'];
                $user_type = $GLOBALS['user_info']['user_type'];
                $enterprise_user_flag = strlen($user_mobile) == 11 && $user_mobile['0'] == '6';
                $user_flag = $enterprise_user_flag || $user_type == '1';
                if ($user_flag && ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1 || is_qiye_site())) {
                    $this->template = 'web/views/user/editpwd_enterprise.html';
                } else {
                    $this->template = "web/views/user/editpwd.html";
                }
                return false;
            }
}
