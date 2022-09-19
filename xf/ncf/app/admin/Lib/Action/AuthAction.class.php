<?php

class AuthAction extends BaseAction{
    public function __construct()
    {
        parent::__construct();
        $this->check_auth();
    }

    /**
     * 验证检限
     * 已登录时验证用户权限, Index模块下的所有函数无需权限验证
     * 未登录时跳转登录
     */
    private function check_auth()
    {
        if(intval(app_conf("EXPIRED_TIME"))>0&&es_session::is_expired())
        {
            es_session::delete(md5(conf("AUTH_KEY")));
            es_session::delete("expire");
        }

        //管理员的SESSION
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $adm_id = intval($adm_session['adm_id']);
        $ajax = intval($_REQUEST['ajax']);
        $is_auth = 0;
        $user_info =  es_session::get("user_info");
        
        $force = false;
        //上次修改密码过后90天强制再次修改密码
        if(time() > $adm_session['password_update_time'] + (90 * 24 * 3600) || $adm_session['force_change_pwd'] != 1 ) {
            $force = true;
        }
        if($force && !in_array( ACTION_NAME, array('do_change_password', 'change_password', 'do_loginout'))){
            header('Location:/m.php?m=Index&a=change_password&force=1');exit;
        }

        if(intval($user_info['id'])>0) //会员允许使用后台上传功能
        {
            if((MODULE_NAME=='File'&&ACTION_NAME=='do_upload')||(MODULE_NAME=='File'&&ACTION_NAME=='do_upload_img')||(MODULE_NAME=='File'&&ACTION_NAME=='ajax_upload_img'))
            {
                $is_auth = 1;
            }
            //set form cache 不验证权限
            if(ACTION_NAME=='setFormCache'){
                $is_auth = 1;
            }
        }


        if($adm_id == 0&&$is_auth==0)
        {
            if($ajax == 0)
            //$this->redirect("Public/login", array('refer' => $_SERVER['REQUEST_URI']));
            $this->redirect("Public/login");
            else
            $this->error(L("NO_LOGIN"),$ajax);
        }

        //开始验证权限，当管理员名称不为默认管理员时
        //开始验证模块是否需要授权
        $sql = "select count(*) as c from ".conf("DB_PREFIX")."role_node as role_node left join ".
                   conf("DB_PREFIX")."role_module as role_module on role_module.id = role_node.module_id ".
                   " where role_node.action ='".ACTION_NAME."' and role_module.module = '".MODULE_NAME."' ".
                   " and role_node.is_effect = 1 and role_node.is_delete = 0 and role_module.is_effect = 1 and role_module.is_delete = 0 ";
        $count = MI()->query($sql);
        $count = $count[0]['c'];

        if(MODULE_NAME!='Index'&&MODULE_NAME!='Lang'&&$count>0&&$is_auth==0)
        {
            //除IndexAction外需验证的权限列表
            $sql = "select count(*) as c from ".conf("DB_PREFIX")."role_node as role_node left join ".
                   conf("DB_PREFIX")."role_access as role_access on role_node.id=role_access.node_id left join ".
                   conf("DB_PREFIX")."role as role on role_access.role_id = role.id left join ".
                   conf("DB_PREFIX")."role_module as role_module on role_module.id = role_node.module_id left join ".
                   conf("DB_PREFIX")."admin as admin on admin.role_id = role.id ".
                   " where admin.id = ".$adm_id." and role_node.action ='".ACTION_NAME."' and role_module.module = '".MODULE_NAME."' ".
                   " and role_node.is_effect = 1 and role_node.is_delete = 0 and role_module.is_effect = 1 and role_module.is_delete = 0 and role.is_effect = 1 and role.is_delete = 0";
            $count = M()->query($sql);
            $count = $count[0]['c'];
            if($count == 0)
            {
                if((MODULE_NAME=='File'&&ACTION_NAME=='do_upload')||(MODULE_NAME=='File'&&ACTION_NAME=='do_upload_img'))
                {
                    echo "<script>alert('".L("NO_AUTH")."');</script>";
                    exit;
                }
                else
                {
                        $this->error(L("NO_AUTH"),$ajax);
                }
            }
        }
    }

    //index列表的前置通知,输出页面标题
    public function _before_index()
    {
        $this->assign("main_title",L(MODULE_NAME."_INDEX"));
    }
    public function _before_trash()
    {
        $this->assign("main_title",L(MODULE_NAME."_INDEX"));
    }

    /**
     * 是否有 对应module 和 action 的权限
     * changlu 2014年7月16日11:19:25
     * @param $module 模块名
     * @param string $action 方法名
     * @return bool
     */
    public function is_have_action_auth($module=MODULE_NAME,$action=ACTION_NAME){
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        if($adm_session['adm_name'] == 'admin'){
            return true;
        }
        $role_id = $adm_session['adm_role_id'];
        $sql = "SELECT count(*) as c FROM `".DB_PREFIX."role_access` WHERE role_id = %d AND module_id IN (SELECT id FROM `".DB_PREFIX."role_module` WHERE module='%s')  AND node_id IN (SELECT id FROM `".DB_PREFIX."role_node` WHERE module_id IN (SELECT id FROM `".DB_PREFIX."role_module` WHERE module='%s' ) AND `action`='%s' )
";
        $sql = sprintf($sql,$role_id,$module,$module,$action);
        $count = M()->query($sql);
        if($count[0]['c']){
            return true;
        }
        return false;
    }
}
?>
