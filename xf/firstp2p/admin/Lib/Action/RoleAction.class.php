<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// | 后台管理员分组
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class RoleAction extends CommonAction{

    public function __construct() {
        parent::__construct();
        $this->assign('all_effect_status', [0 => "所有", -1 => "无效", 1 => "有效"]);
    }

    public function index()
    {
        $where = 'is_delete = 0';
        if ($name = trim($_GET['name'])) {
            $where .= ' AND name LIKE "%'.$name.'%"';
        }

        $effectStatus = $_GET['effect_status'];
        if (intval($effectStatus) != 0) {
            $effectStatus = $effectStatus < 0 ? 0 : $effectStatus;
            $where .= ' AND is_effect = ' . $effectStatus;
        }
        if ($this->is_cn) {
            $allow_ids = str_replace('`',"\"",app_conf('CN_ADMIIN_GROUP_LIST'));
            $where .= ' AND id in ('.$allow_ids.') or is_cn = 1';
        }

        if (isset($_REQUEST['export'])) {
            $this->export($where);
            return false;
        }
        $this->_list($this->model, $where);
	    $this->assign('is_cn',$this->is_cn);
	    $template = $this->is_cn ? 'index_cn' : 'index';
        $this->display($template);
    }

    public function export($where = '')
    {
        ini_set('memory_limit', '1024M');

        $id = isset($_REQUEST[$this->pk_name]) ? trim($_REQUEST[$this->pk_name]) : "";
        if (!empty($id)) {
            $where = ' id IN ('.$id.')';
        }

        $roleList = M('Role')->where($where)->findAll();
        $moduleList = M("RoleModule")->where("is_delete=0 and is_effect=1 and module <> 'Index'")->order("module asc")->findAll();
        $nodeList = M("RoleNode")->where("is_delete=0 and is_effect=1")->findAll();
        $accessList = M("RoleAccess")->findAll();
        //格式化为map结构，降低时间复杂度
        $accessMap = [];
        foreach ($accessList as $val) {
            $accessMap[$val['role_id'] . '-' . $val['module_id'] . '-'. $val['node_id']] = 1;
        }

        $mList = [];
        foreach ($moduleList as $module) {
            $tmp = [];
            $tmp['module_id'] = $module['id'];
            $tmp['module_name'] = $module['name'];
            foreach ($nodeList as $node) {
                if ($module['id'] != $node['module_id']) {
                    continue;
                }
                $tmp['node_id'] = $node['id'];
                $tmp['node_name'] = $node['name'];
                $mList[] = $tmp;
            }
        }

        $list = [];
        foreach ($mList as $val) {
            foreach ($roleList as $role) {
                $val[$role['id']] = 0;
                $accessKey1 = $role['id'] . '-' . $val['module_id'] . '-' . $val['node_id'];
                $accessKey2 = $role['id'] . '-' . $val['module_id'] . '-' . '0';
                if (!empty($accessMap[$accessKey1]) || !empty($accessMap[$accessKey2])) {
                    $val[$role['id']] = 1;
                }
            }
            $list[] = $val;
        }

        //头部
        $header = ['一级功能', '二级功能'];
        foreach ($roleList as $role) {
            $header[] = $role['name'];
        }
        foreach ($header as $key => $item) {
            $header[$key] = mb_convert_encoding($item, 'gbk', 'utf8');
        }

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=管理员组列表.csv");
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        fputcsv($fp, $header);
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        foreach ($list as $item) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            $line = [mb_convert_encoding($item['module_name'], 'gbk', 'utf8'), mb_convert_encoding($item['node_name'], 'gbk', 'utf8')];
            foreach ($roleList as $role) {
                $line[] = !empty($item[$role['id']]) ? 1 : '';
            }
            fputcsv($fp, $line);
        }
    }

    public function trash()
    {
        $condition['is_delete'] = 1;
        $condition['is_cn'] = $this->is_cn ? 1 : 0;
        $this->assign("default_map",$condition);
        parent::index();
    }
    public function add()
    {
        //输出module与action
        $access_list = M("RoleModule")->where("is_delete=0 and is_effect=1 and module <> 'Index'")->order("module asc")->findAll();
           if ($this->is_cn) {
              $allow_moudle = str_replace('`',"\"",app_conf('CN_ADMIIN_MANAGE'));
              $allow_moudle = explode(',',$allow_moudle);
              $allow_moudle = is_array($allow_moudle) ? $allow_moudle : array();
              $conf = str_replace('`',"\"",app_conf('CN_ADMIIN_DEALPROJECT'));
              $conf = json_decode($conf,true);
              $conf = is_array($conf) ? $conf : array();

        }

        foreach($access_list as $k=>$v)
        {
            if ($this->is_cn) {
                if (!in_array($v['module'],$allow_moudle)) {
                    unset($access_list[$k]);
                    continue;
                }
                $node_list = M("RoleNode")->where("is_delete=0 and is_effect=1 and module_id=".$v['id'])->findAll();
                foreach($node_list as $kk=>$vv)
                {
                    if(M("RoleAccess")->where("role_id=".$vo['id']." and module_id=".$v['id']." and node_id =".$vv['id'])->count()>0)
                    {
                        $node_list[$kk]['node_auth'] = 1;
                    }
                    else
                    {
                        $node_list[$kk]['node_auth'] = 0;
                    }
                    $node_list[$kk]['module'] = $v['module'];
                     if (!in_array($vv['name'],$conf['cn'][$v['module']])) {
                         unset($node_list[$kk]);
                         continue;
                     }
                }
                $access_list[$k]['node_list'] = $node_list;
            } else {
                $access_list[$k]['node_list'] = M("RoleNode")->where("is_delete=0 and is_effect=1 and module_id=".$v['id'])->findAll();
            }
        }
        $template = $this->is_cn ? 'add_cn' : 'add';
        $this->assign("access_list",$access_list);
        $this->display($template);
    }
    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign ( 'vo', $vo );
        //输出module与action
        $access_list = M("RoleModule")->where("is_delete=0 and is_effect=1 and module <> 'Index'")->order("module asc")->findAll();
        if ($this->is_cn) {
              $allow_moudle = str_replace('`',"\"",app_conf('CN_ADMIIN_MANAGE'));
              $allow_moudle = explode(',',$allow_moudle);
              $allow_moudle = is_array($allow_moudle) ? $allow_moudle : array();
              $conf = str_replace('`',"\"",app_conf('CN_ADMIIN_DEALPROJECT'));
              $conf = json_decode($conf,true);
              $conf = is_array($conf) ? $conf : array();

        }
        foreach($access_list as $k=>$v)
        {
            if ($this->is_cn) {
                if (!in_array($v['module'],$allow_moudle)) {
                    unset($access_list[$k]);
                    continue;
                }
            }
            if(M("RoleAccess")->where("role_id=".$vo['id']." and module_id=".$v['id']." and node_id =0")->count()>0)
            {
                $access_list[$k]['module_auth'] = 1;  //当前模块被授权
            }
            else
            {
                $access_list[$k]['module_auth'] = 0;
            }
            $node_list = M("RoleNode")->where("is_delete=0 and is_effect=1 and module_id=".$v['id'])->findAll();
            foreach($node_list as $kk=>$vv)
            {
                if(M("RoleAccess")->where("role_id=".$vo['id']." and module_id=".$v['id']." and node_id =".$vv['id'])->count()>0)
                {
                    $node_list[$kk]['node_auth'] = 1;
                }
                else
                {
                    $node_list[$kk]['node_auth'] = 0;
                }
                if ($this->is_cn) {
                    $node_list[$kk]['module'] = $v['module'];
                     if (!in_array($vv['name'],$conf['cn'][$v['module']])) {
                         unset($node_list[$kk]);
                         continue;
                     }
                }
            }
            $access_list[$k]['node_list'] = $node_list;
            //非模块授权时的是否全选
            if(M("RoleAccess")->where("role_id=".$vo['id']." and module_id=".$v['id']." and node_id <>0")->count() == M("RoleNode")->where("is_delete=0 and is_effect=1 and module_id=".$v['id'])->count()&&M("RoleNode")->where("is_delete=0 and is_effect=1 and module_id=".$v['id'])->count() != 0)
            {
                //全选
                $access_list[$k]['check_all'] = 1;
            }
            else
            {
                $access_list[$k]['check_all'] = 0;
            }
        }
        $this->assign("access_list",$access_list);


        $this->display ();
    }
    //相关操作
    public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $info = M(MODULE_NAME)->where("id=".$id)->getField("name");
        $c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField("is_effect");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=".$id)->setField("is_effect",$n_is_effect);
        save_log($info.l("SET_EFFECT_".$n_is_effect),1);
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)    ;
    }
    public function insert() {
        B('FilterString');
        $data = M(MODULE_NAME)->create ();
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/add"));
        if(!check_empty($data['name']))
        {
            $this->error(L("ROLE_NAME_EMPTY_TIP"));
        }
        // 更新数据
        $log_info = $data['name'];
        $role_id=M(MODULE_NAME)->add($data);
        if (false !== $role_id) {
            //开始关联节点
            $role_access = $_REQUEST['role_access'];
            foreach($role_access as $k=>$v)
            {
                //开始提交关联
                $item = explode("_",$v);
                if($item[1]==0)
                {
                    //模块授权
                    M("RoleAccess")->where("role_id=".$role_id." and module_id=".$item[0])->delete();
                }
                else
                {
                    //节点授权
                    M("RoleAccess")->where("role_id=".$role_id." and module_id=".$item[0]." and node_id=".$item[1])->delete();
                }
                $access_item['role_id'] = $role_id;
                $access_item['node_id'] = $item[1];
                $access_item['module_id'] = $item[0];
                M("RoleAccess")->add($access_item);
            }
            //成功提示
            save_log($log_info.L("INSERT_SUCCESS"),1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("INSERT_FAILED"),0);
            $this->error(L("INSERT_FAILED"));
        }
    }

    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create ();
        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("name");
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
        if(!check_empty($data['name']))
        {
            $this->error(L("ROLE_NAME_EMPTY_TIP"));
        }
        // 更新数据
        $list=M(MODULE_NAME)->save ($data);
        if (false !== $list) {
            //成功提示
            $role_id = $data['id'];
            M("RoleAccess")->where("role_id=".$role_id)->delete();
            //开始关联节点
            $role_access = $_REQUEST['role_access'];
            foreach($role_access as $k=>$v)
            {
                //开始提交关联
                $item = explode("_",$v);
                if($item[1]==0)
                {
                    //模块授权
                    M("RoleAccess")->where("role_id=".$role_id." and module_id=".$item[0])->delete();
                }
                else
                {
                    //节点授权
                    M("RoleAccess")->where("role_id=".$role_id." and module_id=".$item[0]." and node_id=".$item[1])->delete();
                }
                $access_item['role_id'] = $role_id;
                $access_item['node_id'] = $item[1];
                $access_item['module_id'] = $item[0];
                M("RoleAccess")->add($access_item);
            }
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"));
        }
    }

    public function delete() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
                $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
                $rel_data = M(MODULE_NAME)->where($condition)->findAll();
                foreach($rel_data as $data)
                {
                    $info[] = $data['name'];
                    //开始验证分组下是否存在管理员
                    if(M("Admin")->where("is_effect = 1 and is_delete = 0 and role_id=".$data['id'])->count()>0)
                    {
                        $this->error ($data['name'].l("EXIST_ADMIN"),$ajax);
                    }
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->setField ( 'is_delete', 1 );
                if ($list!==false) {
                    save_log($info.l("DELETE_SUCCESS"),1);
                    $this->success (l("DELETE_SUCCESS"),$ajax);
                } else {
                    save_log($info.l("DELETE_FAILED"),0);
                    $this->error (l("DELETE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    public function restore() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
                $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
                $rel_data = M(MODULE_NAME)->where($condition)->findAll();
                foreach($rel_data as $data)
                {
                    $info[] = $data['name'];
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->setField ( 'is_delete', 0 );
                if ($list!==false) {
                    save_log($info.l("RESTORE_SUCCESS"),1);
                    $this->success (l("RESTORE_SUCCESS"),$ajax);
                } else {
                    save_log($info.l("RESTORE_FAILED"),0);
                    $this->error (l("RESTORE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
                $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
                $role_access_condition = array ('role_id' => array ('in', explode ( ',', $id ) ) );
                $rel_data = M(MODULE_NAME)->where($condition)->findAll();
                foreach($rel_data as $data)
                {
                    $info[] = $data['name'];
                    //开始验证分组下是否存在管理员
                    if(M("Admin")->where("is_effect = 1 and is_delete = 0 and role_id=".$data['id'])->count()>0)
                    {
                        $this->error ($data['name'].l("EXIST_ADMIN"),$ajax);
                    }
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->delete();
                M("RoleAccess")->where($role_access_condition)->delete();
                M("Admin")->where($role_access_condition)->delete();
                if ($list!==false) {
                    save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                    $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
                } else {
                    save_log($info.l("FOREVER_DELETE_FAILED"),0);
                    $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
}
?>
