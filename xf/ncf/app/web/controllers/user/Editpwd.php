<?php
/**
 * 修改密码页面
 * @author wenyanlei@ucfgroup.com
 */
namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;

class Editpwd extends BaseAction
{
    private $_error = null;
    public function init ()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'error' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
        }
    }

    public function invoke ()
    {
        if ($this->check_login()) {
            // 主站判断是不是企业账户，如果是企业账户，走以前的模板
            $user_mobile = $GLOBALS['user_info']['mobile'];
            $user_type = $GLOBALS['user_info']['user_type'];
            $enterprise_user_flag = strlen($user_mobile) == 11 && $user_mobile['0'] == '6';
            $user_flag = $enterprise_user_flag || $user_type == '1';
            if ($user_flag && ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1 || $this->isEnterprise)) {
                return $this->template = 'web/views/user/editpwd_enterprise.html';
            } else {
                $switch = app_conf('TURN_ON_FIRSTLOGIN');
                if ($switch == 2) {
                    $msg = app_conf('USER_MAINTENANCE_MSG');
                    return $this->show_error($msg, '系统维护中', 0, 1);
                } else {
                    $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
                    $this->tpl->assign('title', app_conf('SHOP_TITLE'));
                }
                if ($this->form->data['error'] == 'error_jump' || $this->_error) {
                    $msg = "验证错误";
                    if (\es_session::get('DoModifyPwd_pwd_error')) {
                        $msg = \es_session::get('DoModifyPwd_pwd_error');
                        \es_session::delete('DoModifyPwd_idno_error');
                    }
                    return $this->show_error('', $msg, 0, 0,url('user/editpwd'));
                }
            }
        }
    }
}