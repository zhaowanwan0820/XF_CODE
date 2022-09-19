<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

//后台验证的基础类

class AuthAction extends BaseAction{

    //机构管理后台
    protected $orgData = [];

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

        $this->orgData = empty($adm_session['org_id']) ? [] : $adm_session['org_id'];

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
        }

        if ($adm_id == 0 && $is_auth == 0) {
            if($ajax == 0) {
                $param = [];
                if (isset($_COOKIE['adminfrom']) && $_COOKIE['adminfrom'] == 'org') {
                    $param['from'] = 'org';
                }
                $this->redirect(u("Public/login", $param));
            } else {
                $this->error(L("NO_LOGIN"), $ajax);
            }
        }

        // ncfph桥接过来的会员列表，已经在ncfph验证权限并登录，此处不再验证,by liguizhi
        if ($this->is_cn && isset($adm_session['adminAuthPassed']) && $adm_session['adminAuthPassed']) {
            return true;
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
                //节点授权不足，开始判断是否有模块授权
                $module_sql = "select count(*) as c from ".conf("DB_PREFIX")."role_access as role_access left join ".
                               conf("DB_PREFIX")."role as role on role_access.role_id = role.id left join ".
                               conf("DB_PREFIX")."role_module as role_module on role_module.id = role_access.module_id left join ".
                               conf("DB_PREFIX")."admin as admin on admin.role_id = role.id ".
                               " where admin.id = ".$adm_id." and role_module.module = '".MODULE_NAME."' ".
                               " and role_access.node_id = 0".
                               " and role_module.is_effect = 1 and role_module.is_delete = 0 and role.is_effect = 1 and role.is_delete = 0";
                $module_count = MI()->query($module_sql);
                $module_count = $module_count[0]['c'];
                if($module_count == 0)
                {
                    if((MODULE_NAME=='File'&&ACTION_NAME=='do_upload')||(MODULE_NAME=='File'&&ACTION_NAME=='do_upload_img'))
                    {
                        echo "<script>alert('".L("NO_AUTH")."');</script>";
                        exit;
                    }
                    else
                    {
                        if(MODULE_NAME=='UserCarry' && ACTION_NAME=='edit'){
                            echo L("NO_AUTH");
                            exit;
                        }elseif(MODULE_NAME=='Deal' && ACTION_NAME=='show_detail'){
                            echo L("NO_AUTH");
                            exit;
                        }else{
                            $this->error(L("NO_AUTH"),$ajax);
                        }
                    }
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
        $sql = "SELECT count(*) as c FROM `firstp2p_role_access` WHERE role_id = %d AND module_id IN (SELECT id FROM `firstp2p_role_module` WHERE module='%s')  AND node_id IN (SELECT id FROM `firstp2p_role_node` WHERE module_id IN (SELECT id FROM `firstp2p_role_module` WHERE module='%s' ) AND `action`='%s' )
";
        $sql = sprintf($sql,$role_id,$module,$module,$action);
        $count = M()->query($sql);
        if($count[0]['c']){
            return true;
        }
        return false;
    }


    /**
     * 机构管理后台筛选条件
     */
     public function orgCondition($isArr = true, $table = '')
     {
        $timeline = 1525314600 - 3600*8; //时间:2018-5-03 10:30:00

        if ($isArr) {
            $map = [];
            if (!empty($this->orgData)) {
                $map[$this->orgData['field']] = $this->orgData['id'];
            }
            if (isset($this->orgData['time']) && in_array($this->orgData['time'], ['gt', 'lt'])) {
                $map['create_time'] = [$this->orgData['time'], $timeline];
            }
            return $map;
        }
        //ELSE
        $sqlStr = '';
        if (!empty($this->orgData)) {
            $field = $table ? $table.'.'.$this->orgData['field']:$this->orgData['field'];
            $sqlStr .= ' AND '.$field.'='.intval($this->orgData['id']).' ';
            if (isset($this->orgData['time']) && in_array($this->orgData['time'], ['gt', 'lt'])) {
                $create = $table ? $table.'.'.'create_time' : 'create_time';
                $comp = ($this->orgData['time'] == 'gt') ? '>' : '<';
                $sqlStr .= ' AND '.$create.$comp.$timeline.' ';
            }
        }
        return $sqlStr;

     }

}

