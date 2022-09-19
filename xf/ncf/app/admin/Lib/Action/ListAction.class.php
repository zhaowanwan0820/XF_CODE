<?php
class ListAction extends CommonAction{

    public function index()
    {
        //输出module与action
        //$nav_list = M("RoleNav")->where("is_delete=0 and is_effect=1")->order("sort asc")->findAll();//getField('id,name',true);
        $nav_list = M("RoleNav")->where("")->order("sort asc")->findAll();//getField('id,name',true);
        $tree = array();
        foreach($nav_list as $nav){
            $arr = array();
            $nav_id = $nav['id'];
            //$group_list = M("RoleGroup")->where("nav_id = " .$nav['id'] ." and is_delete=0 and is_effect=1")->order("sort asc")->findAll();
            $group_list = M("RoleGroup")->where("nav_id = " .$nav['id'])->order("sort asc")->findAll();
            if(!empty($group_list)){
                $nav['get'] = 'navid='.$nav['id'];
                $nav['t'] = $nav['name'];
                $nav['open'] = true;
                $tree[] = $nav;
                $arr_node = $this->_createnode($group_list,$nav_id,"group_id");
                $tree = array_merge($tree,$arr_node);
//                 foreach($group_list as $group){

//                 }
            }else{
                $nav['isParent'] = true;
                $nav['t'] = $nav['name'];
                $nav['get'] = 'navid='.$nav['id'];
                $nav['open'] = true;
                $tree[] = $nav;
            }
        }

        $this->assign("tree",json_encode($tree));
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
            $node_list = M("RoleNode")->where("$type = " .$group['id'])->findAll();
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
                    $arr['id'] = $nav_id.$group['id'].$node['id'];
                    $arr['pId'] = $nav_id.$group['id'];
                    $arr['name'] = $node['name'];
                    $arr['t'] = $node['name'];
                    $arr['get'] = 'navid='.$nav_id.'&gid='.$group['id'].'&mid='.$node['module_id'].'&nid='.$node['id'];
                    $tree[] = $arr;
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
}
