<?php
use libs\db\Db;

class ListAction extends CommonAction{

    //所有已显示的节点
    protected $isValidNode = array();

    public function index()
    {
        //输出module与action
        //$nav_list = M("RoleNav")->where("is_delete=0 and is_effect=1")->order("sort asc")->findAll();//getField('id,name',true);
        $nav_list = M("RoleNav")->where("")->order("sort asc")->findAll();//getField('id,name',true);
        $tree = array();
        //获取menu
        if($this->is_cn && app_conf('CN_ADMIN_SHOW_MENU')) {
            $allow_menus = explode("::",app_conf('CN_ADMIN_SHOW_MENU'));
            $rs1 =$this->_rebuild(CN_ADMIIN_LEFT_MENU,6);
            $rs2 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_YONGHU,7);
            $rs3 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_TOUZIQUAN,1);
            $rs4 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_XITONGQUANXIAN,3);
            $rs5 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_HONGBAO,14);
            $rs6 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_ORDER,8);
            $rs7 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_HETONG,10);
            $rs = array_merge_recursive($rs1,$rs2,$rs3,$rs4,$rs5,$rs6,$rs7);
        }

        foreach($nav_list as $nav) {
            $arr = array();
            $nav_id = $nav['id'];
            //$group_list = M("RoleGroup")->where("nav_id = " .$nav['id'] ." and is_delete=0 and is_effect=1")->order("sort asc")->findAll();
            $group_list = M("RoleGroup")->where("nav_id = " . $nav['id'])->order("sort asc")->findAll();
            if ($this->is_cn){
                if (!empty($allow_menus) && is_array($allow_menus)) {
                    if (!in_array($nav['name'], $allow_menus)) continue;
                }
                foreach ($group_list as $k => &$v) {
                    if (!in_array($v['name'], $rs)) {
                        unset($group_list[$k]);
                    }
                }
            }
            if(!empty($group_list)){
                $nav['get'] = 'navid='.$nav['id'];
                $nav['t'] = $nav['name'];
                $nav['open'] = true;
                $tree[] = $nav;
                $arr_node = $this->_createnode($group_list,$nav_id,"group_id");
                $tree = array_merge($tree,$arr_node);
            }else{
                $nav['isParent'] = true;
                $nav['t'] = $nav['name'];
                $nav['get'] = 'navid='.$nav['id'];
                $nav['open'] = true;
                $tree[] = $nav;
            }
        }

        // 收集所有不显示并且在功能列表菜单中无法找到的节点
        $nodeInfo = Db::getInstance('firstp2p')->getAll("SELECT * FROM firstp2p_role_node WHERE id not in (" . join(',', $this->isValidNode) .")");
        foreach ($nodeInfo as $item) {
            $tree[] = array(
                'id' => $item['id'],
                'name' => $item['name'],
                'get' => 'navid=0&gid=0&mid='.$item['module_id'].'&nid='.$item['id'],
            );
        }

        $this->assign("tree",json_encode($tree));
        $this->assign("is_cn",$this->is_cn);
        $this->display();
    }
    /**
     * 显示节点信息
     */
    public function nodeshow(){

        $navid = intval($_REQUEST['navid']);
        $gid = intval($_REQUEST['gid']);
        $mid = intval($_REQUEST['mid']);
        $nid = intval($_REQUEST['nid']);

        $is_effect = 1;
        $is_delete = 0;
        $sort = 0;
        $del_node = 0;//是否删除节点
        if($navid){
            $nav_info = M("RoleNav")->where("id=".$navid)->find();
            $this->assign("nav",$nav_info);
            $is_effect = $nav_info['is_effect'];
            $is_delete = $nav_info['is_delete'];
            $sort      = $nav_info['sort'];

            $group_list = M("RoleGroup")->where("nav_id=".$navid)->count();
            if(!$group_list){
                $del_node = $navid;
                $type = "Nav";
            }
        }
        if($gid){
            $group_info = M("RoleGroup")->where("id=".$gid)->find();
            $module_list = M("RoleModule")->findAll();
            $this->assign("group",$group_info);
            $this->assign("modulelist",$module_list);
            $is_effect = $group_info['is_effect'];
            $is_delete = $group_info['is_delete'];
            $sort      = $group_info['sort'];

            $node_list = M("RoleNode")->where("group_id=".$gid)->count();
            if(!$node_list){
                $del_node = $gid;
                $type = "Group";
            }
        }
        if($nid){//修改 node
            $node_info = M("RoleNode")->where("id=".$nid)->find();
            $this->assign("node",$node_info);
            $module_info = M("RoleModule")->where("id=".$mid)->find();
            $this->assign("module",$module_info);
            $is_effect = $node_info['is_effect'];
            $is_delete = $node_info['is_delete'];
            $del_node = $nid;
            $type = "Node";
        }

        $this->assign("is_delete",$is_delete);
        $this->assign("is_effect",$is_effect);
        $this->assign("sort",$sort);
        $this->assign("del_node",$del_node);
        $this->assign("type",$type);
        $html = $this->fetch();
        echo $html;
        exit;
    }
    /**
     * 节点修改 
     */
    public function nodeedit(){
        $navid = intval($_REQUEST['navid']);
        $gid = intval($_REQUEST['gid']);
        $mid = intval($_REQUEST['mid']);
        $nid = intval($_REQUEST['nid']);
        $module_id = intval($_REQUEST['module_id']);

        $mid = intval($_REQUEST['mid']);
        $nid = intval($_REQUEST['nid']);

        $is_effect = intval($_REQUEST['is_effect']);
        $is_delete = intval($_REQUEST['is_delete']);
        $is_show = intval($_REQUEST['is_show']);
        $sort = intval($_REQUEST['sort']);

        B('FilterString');
        $name = $_REQUEST['name'];
        $action = $_REQUEST['action'];
        $group_name = $_REQUEST['group_name'];
        $module_name = $_REQUEST['module_name'];
        $module = $_REQUEST['module'];
        $node_name = $_REQUEST['node_name'];

        if(!$name && !$node_name){
            $this->error("参数错误！",1);
            exit;
        }

        $data = array();
        $data['is_effect'] = $is_effect;
        $data['is_delete'] = $is_delete;
        $data['group_id'] = $is_show;

        if($nid && $name && $action){//修改 role_node
            $data['name'] = $name;
            $data['action'] = $action;
            M("RoleNode")->where("id=".$nid)->save($data);
            $this->ajaxReturn()    ;
            exit;
        }
        $data['sort'] = $sort;
        if($gid){// 修改 group_node
            if(($module && $module_name) ||  $module_id !=-1){//新建module 并添加
                if($module_id == -1){//新建module
                    $data['name'] = $module_name;
                    $data['module'] = $module;
                    M("RoleModule")->add($data);
                    $mid = M("RoleModule")->getLastInsID();
                }else{//编辑
                    $data['name'] = $module_name;
                    $data['module'] = $module;
                    M("RoleModule")->where("id=".$module_id)->save($data);
                    $mid = M("RoleModule")->getLastInsID();
                    $mid = $module_id;

                    //一级菜单点击“全选”后，若二级菜单增加新功能，则此新功能不勾选且一级菜单的“全选”消失。
                    $access_list = M('RoleAccess')->where('node_id = 0 and module_id = ' . $module_id)->findAll();
                    $node_list = M("RoleNode")->where('is_effect = 1 and is_delete = 0 and module_id = ' . $module_id)->findAll();
                    foreach ($access_list as $access) {

                        //先补充模块下的节点权限
                        foreach ($node_list as $node) {
                            $access_info = M('RoleAccess')->where('role_id = ' . $access['role_id'] . ' and module_id = ' . $module_id . ' and node_id = ' . $node['id'])->find();
                            if (empty($access_info)) {
                                M("RoleAccess")->add(['role_id' => $access['role_id'], 'module_id' => $module_id, 'node_id' => $node['id']]);
                            }
                        }

                        //模块下有节点，去掉模块权限
                        if (!empty($node_list)) {
                            M('RoleAccess')->where('node_id = 0 and module_id = ' . $module_id . ' and role_id = ' . $access['role_id'])->delete();
                        }
                    }
                }

                unset($data['module']);
                $data['name'] = $node_name;
                $data['action'] = $action;
//                  $data['group_id'] = $gid;
                $data['module_id'] = $mid;
                M("RoleNode")->add($data);
            }else{//修改 group_node;
                $data['name'] = $name;
                M("RoleGroup")->where("id=".$gid)->save($data);
            }
            $this->ajaxReturn()    ;
            exit;
        }

        if($group_name){//新建组
            $data['name'] = $group_name;
            $data['nav_id'] = $navid;
            M("RoleGroup")->add($data);
        }else{//编辑 nav
            $data['name'] = $name;
            M("RoleNav")->where("id=".$navid)->save($data);
        }
        $this->ajaxReturn()    ;
        exit;
    }

    /**
     * 功能点物理删除
     */
    public function nodedel()
    {
        $nid = intval($_POST['nid']);
        $type = $_POST['type'];
        if(!$nid){
            $this->error("参数错误！");exit;
        }
        if(!in_array($type, array('Node','Group','Nav'))){
            $this->error("参数错误！");exit;
        }
        $model = "Role".$type;
        M($model)->where("id=".$nid)->delete();
        $this->ajaxReturn()    ;
        exit;
    }

    /**
     * 生成叶子节点
     * @param array $list
     * @param int $parentid
     * @param string $type group_id 或者是  module_id
     */
    protected function _createnode($list,$parentid,$type="group_id"){
        $tree = array();
        $nav_id = $parentid;
        $mid = 0;//组合 module 的 module id
        foreach($list as $group){
            //$deals = $GLOBALS['db']->getAll("select id from ".DB_PREFIX."deal where is_effect = 1 and deal_status not in (3,5) and is_delete = 0 AND load_money/borrow_amount <= 1");
            //$module_list = M("RoleModule")->where("nav_id = " .$nav['id'] ." and is_delete=0 and is_effect=1")->order("sort asc")->findAll();
            $node_list = M("RoleNode")->where("$type = " .$group['id'])->findAll();
            //$node_list = M("RoleNode")->where("group_id = " .$group['id'] ." and is_delete=0 and is_effect=1")->findAll();
            $node_list_m = M("RoleNode")->where("$type = " .$group['id'])->field('DISTINCT module_id')->select();
            $mids = '';
            if($node_list_m){
                foreach($node_list_m as $v){
                    $mids .= $v['module_id'].",";
                }
                $mids = trim($mids,",");
                $gid = $group['id'];
                if($mids){
                    //没有group的 node 节点
                    $node_list_n = M("RoleNode")->where("module_id in(".$mids .") and group_id=0")->findAll();
                    if($node_list_n){
                        $node_list = array_merge($node_list,$node_list_n);
                    }
                }
            }

            if(!empty($node_list)){
                $arr['id'] = $nav_id.$group['id'];
                $arr['pId'] = $nav_id;
                $arr['name'] = $group['name'];
                $arr['t'] = $group['name'];
                $arr['get'] = 'navid='.$nav_id.'&gid='.$group['id'];
                $tree[] = $arr;
                $arr = array();
                foreach($node_list as $node){
                    if ($this->is_cn) {
                        $rs1 =$this->_rebuild(CN_ADMIIN_LEFT_MENU,6);
                        $rs2 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_YONGHU,7);
                        $rs3 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_TOUZIQUAN,1);
                        $rs4 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_XITONGQUANXIAN,3);
                        $rs5 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_HONGBAO,14);
                        $rs6 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_ORDER,8);
                        $rs7 =$this->_rebuild(CN_ADMIIN_LEFT_MENU_HETONG,10);
                        $rs = array_merge_recursive($rs1,$rs2,$rs3,$rs4,$rs5,$rs6,$rs7);
                        if (!in_array($node['name'], $rs)) continue;
                    }
                    $arr['id'] = $nav_id.$group['id'].$node['id'];
                    $arr['pId'] = $nav_id.$group['id'];
                    $arr['name'] = $node['name'];
                    $arr['t'] = $node['name'];
                    $arr['get'] = 'navid='.$nav_id.'&gid='.$group['id'].'&mid='.$node['module_id'].'&nid='.$node['id'];
                    $tree[] = $arr;
                    // 收集所有已显示的节点
                    array_push($this->isValidNode, $node['id']);
                    $arr = array();
                }
            }else{
                $arr['id'] = $nav_id.$group['id'];
                $arr['pId'] = $nav_id;
                $arr['name'] = $group['name'];
                $arr['t'] = $group['name'];
                $arr['isParent'] = true;
                $arr['get'] = 'navid='.$nav_id.'&gid='.$group['id'];
                $tree[] = $arr;
                $arr = array();
            }
        }
        return $tree;
    }
   
    protected function _rebuild($reKey,$menuId){
        $cn_menus = str_replace('`',"\"",app_conf($reKey));
        $cn_menus = json_decode($cn_menus,true);
        $cn_menus = is_array($cn_menus) ? $cn_menus : array();
        $build_key = $build_val = array();
        if (!empty($cn_menus[$menuId]) && is_array($cn_menus[$menuId])){
             foreach ($cn_menus[$menuId] as $key=>$val) {
                 foreach ($val as $k=>$v) {
                     $build_val[]=$v;
                 }
                 $build_key[] = $key;
             }
        }
        unset($cn_menus);
        return array_merge($build_key,$build_val);
    }

    /**
     * 添加列表项目
     */
    public function addListSave()
    {
        $name = isset($_POST['name']) ? addslashes(trim($_POST['name'])) : '';

        if (empty($name)) {
            throw new \Exception('名称输入不正确，请重新输入');
        }
        $navList = M("RoleNav")->where("")->order("sort desc")->find();
        $sort = $navList['sort'] + 1;

        $data = array(
            'name' => $name,
            'sort' => $sort,
            'is_delete' => 0,
            'is_effect' => 1,
        );

        M('RoleNav')->add($data);
        $this->success('操作成功', 0, '?m=List&a=index');

    }

}
?>
