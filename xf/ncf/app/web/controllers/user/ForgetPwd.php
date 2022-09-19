<?php
/**
 * 忘记密码
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;
use web\controllers\BaseAction;
use libs\web\Form;
error_reporting(E_ALL);
ini_set('display_errors', 1);

class ForgetPwd extends BaseAction
{
    private $_error = null;
    public function init ()
    {
        $this->form = new Form();
        $this->form->rules = array(
                'error' => array('filter' => 'string')
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
        }
    }
    public function invoke ()
    {
        $switch = app_conf('TURN_ON_FIRSTLOGIN');
        if ($switch == 2) {
            $msg = app_conf('USER_MAINTENANCE_MSG');
            return $this->show_error($msg, '系统维护中', 0, 1);
        } else {
            $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
            $this->tpl->assign('title', app_conf('SHOP_TITLE'));
            $this->tpl->assign("page_title", '忘记密码');
        }
        if ($this->form->data['error'] == 'error_jump' || $this->_error) {
            $msg = '验证错误';
            if (\es_session::get('DoModifyPwd_idno_error')) {
                $msg = \es_session::get('DoModifyPwd_idno_error');
                \es_session::delete('DoModifyPwd_idno_error');
            }
            if (\es_session::get('Phone_not_exist')) {
                $msg = \es_session::get('Phone_not_exist');
                \es_session::delete('Phone_not_exist');
            }
            return $this->show_error('', $msg, 0, 0,url('user/ForgetPwd'));
        }
    }
}
