<?php
/**
 * 忘记密码
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;
use web\controllers\BaseAction;

class ForgetPassword extends BaseAction
{
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
    }
}
