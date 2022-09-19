<?php
class IndexOrgAction extends AuthAction
{
    public function index()
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $template = empty($adm_session['org_id']) ? 'index' : 'index_org';
        $this->display($template);
    }

    public function top()
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $sql = 'SELECT ra.module_id as id, rm.name, rm.module FROM firstp2p_role_access as ra left join firstp2p_role_module as rm on rm.id = ra.module_id where ra.role_id='.intval($adm_session['adm_role_id']).' group by ra.module_id';
        $navs = M()->query($sql);

        $this->assign("navs",$navs);
        $this->assign("adm_data",$adm_session);
        $this->display();
    }

    public function left()
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_id = intval($adm_session['adm_id']);

        $module_id = intval($_REQUEST['id']);
        $navData = ['id' => '1' ,'name' => '管理列表', 'nodes'=> ''];
        if (!$module_id) {
            $nodes[] = ['id'=> 1, 'm' => 'IndexOrg', 'a'=>'welcome', 'name' => '首页'];
        } else {
            $module_name = trim($_REQUEST['module']);
            $sql = 'SELECT rn.id as nid, rn.action as a, rn.name, "'.$module_name.'" as m FROM firstp2p_role_node rn left join firstp2p_role_access ra on rn.id=ra.node_id where ra.role_id='.$adm_session['adm_role_id'].' and rn.module_id='.$module_id.' and rn.group_id>0 and rn.is_effect=1 and rn.is_delete=0';
            $nodes = M()->query($sql);
        }
        $navData['nodes'] = $nodes;
        $menus[] = $navData;
        $this->assign("menus", $menus);
        $this->display();
    }

    public function main()
    {
        $this->display();
    }

    public function footer()
    {
        $this->display();
    }

    public function welcome()
    {
        echo "<html><head><meta charset='utf-8'></head><body style='margin:100px 0;text-align:center'>
        <h1 style='color:#666;font-size:50px;'>资产交易管理系统</h1><h3 style='color:#999'>您的所有操作行为将会被记录，请谨慎操作！</h3></body></html>";
    }

}
?>
