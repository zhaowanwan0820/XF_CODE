<?php
/**
 * 更新修改密码页面
 * @author zhaohui(zhaohui3@ucfgroup.com)
 */
namespace web\controllers\user;
use web\controllers\BaseAction;

class Renewpwd extends BaseAction
{
    public function invoke ()
    {
        $switch = app_conf('TURN_ON_FIRSTLOGIN');
        if ($switch == 2) {
            $msg = app_conf('USER_MAINTENANCE_MSG');
            return $this->show_error($msg, '系统维护中', 0, 1);
        } else{
            $this->tpl->assign('title', app_conf('SHOP_TITLE'));
        }
    }
}