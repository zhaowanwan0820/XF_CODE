<?php
/**
 * 修改邮箱页面
 * @author wenyanlei@ucfgroup.com
 */
namespace web\controllers\user;
use web\controllers\BaseAction;

class Editemail extends BaseAction
{

    public function init ()
    {
        return $this->check_login();
    }

    public function invoke ()
    {
        $switch = app_conf('TURN_ON_FIRSTLOGIN');

        if ($switch == 2) {
            $msg = app_conf('USER_MAINTENANCE_MSG');
            return $this->show_error($msg, '系统维护中', 0, 1);
        } else {
            $user_id = intval ( $GLOBALS ['user_info'] ['id'] );
            $user_info = $this->rpc->local('UserService\getUser', array($user_id));
            $this->tpl->assign('real_old_email', $user_info['email']);
            $this->tpl->assign('title', app_conf('SHOP_TITLE'));
        }
    }
}
