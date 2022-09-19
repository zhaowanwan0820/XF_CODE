<?php

class IndexAction extends AuthAction {
    //首页
    public function index() {
        //shyf del
        $this->display();
    }


    //框架头
    public function top() {
        $condition = "is_effect=1 and is_delete=0";
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_id = intval($adm_session['adm_id']);
        if ($adm_id == '4') {
            $condition .= " and id='18'";
        }

        $navs = M("RoleNav")->where($condition)->order("sort asc")->findAll();
        $this->assign("navs", $navs);

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $this->assign("adm_data", $adm_session);
        $this->display();
    }

    //框架左侧
    public function left() {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_id = intval($adm_session['adm_id']);

        $nav_id = intval($_REQUEST['id']);
        $nav_group = M("RoleGroup")->where("nav_id=" . $nav_id . " and is_effect = 1 and is_delete = 0")->order("sort asc")->findAll();
        foreach ($nav_group as $k => $v) {
            $sql = "select role_node.`action` as a,role_module.`module` as m,role_node.id as nid,role_node.name as name from " . conf("DB_PREFIX") . "role_node as role_node left join " .
                conf("DB_PREFIX") . "role_module as role_module on role_module.id = role_node.module_id " .
                "where role_node.is_effect = 1 and role_node.is_delete = 0 and role_module.is_effect = 1 and role_module.is_delete = 0 and role_node.group_id = " . $v['id'] . " order by role_node.id asc";

            $nav_group[$k]['nodes'] = M()->query($sql);
        }
        $this->assign("menus", $nav_group);
        $this->display();
    }

    //默认框架主区域
    public function main() {
        //会员数
        //$total_user = MI("User")->count();
        //$total_verify_user = M("User")->where("is_effect=1")->count();
        //$total_verify_user = MI("User")->where("idcardpassed=1")->count();
        //$this->assign("total_user",$total_user);
        //$this->assign("total_verify_user",$total_verify_user);


        $this->display();
    }

    //底部
    public function footer() {
        $this->display();
    }

    //修改管理员密码
    public function change_password() {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $this->assign("adm_data",$adm_session);
        $this->assign("force", $_REQUEST['force']);
        $this->display();
    }
    public function do_change_password()
    {
        $adm_id = intval($_REQUEST['adm_id']);
        if(!check_empty($_REQUEST['adm_password']))
        {
            $this->error(L("ADM_PASSWORD_EMPTY_TIP"));
        }
        if(!check_empty($_REQUEST['adm_new_password']))
        {
            $this->error(L("ADM_NEW_PASSWORD_EMPTY_TIP"));
        }
         if($_REQUEST['adm_password'] == $_REQUEST['adm_new_password'])
        {
            $this->error(L("ADM_NEW_PASSWORD_CAN_NOT_USE_LAST_ONE"));
        }
       if($_REQUEST['adm_confirm_password']!=$_REQUEST['adm_new_password'])
        {
            $this->error(L("ADM_NEW_PASSWORD_NOT_MATCH_TIP"));
        }
        if(M("Admin")->where("id=".$adm_id)->getField("adm_password")!=md5($_REQUEST['adm_password']))
        {
            $this->error(L("ADM_PASSWORD_ERROR"));
        }
        vendor('Psecio.Pwdcheck.Password');
        $pwdObj = new \Psecio\Pwdcheck\Password();
        $pwdObj->evaluate($_REQUEST['adm_new_password']);
        if($pwdObj->getScore()<80){
            $this->error('密码强度不够');
        }
        M("Admin")->where("id=".$adm_id)->setField( "adm_password", md5($_REQUEST['adm_new_password']));
        M("Admin")->where("id=".$adm_id)->setField( "force_change_pwd", 1);
        M("Admin")->where("id=".$adm_id)->setField( "password_update_time", time());
        save_log(M("Admin")->where("id=".$adm_id)->getField("adm_name").L("CHANGE_SUCCESS"),1);
        //$this->success(L("CHANGE_SUCCESS"));

        $this->redirect(u("Public/do_loginout"));

    }


    public function welcome(){
        echo "<html><head><meta charset='utf-8'></head><body style='margin:100px 0;text-align:center'>
        <h1 style='color:#666;font-size:50px;'>网信普惠后台管理系统</h1><h3 style='color:#999'>您的所有操作行为将会被记录，请谨慎操作！</h3></body></html>";
    }

}

?>
