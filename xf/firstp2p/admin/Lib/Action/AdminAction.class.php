<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class AdminAction extends CommonAction{

    public function __construct() {
        parent::__construct();
        $this->assign('all_effect_status', [0 => "所有", -1 => "无效", 1 => "有效"]);
    }

    public function index()
    {
        $where = 'is_delete = 0';

        if ($roleName = trim($_GET['role_name'])) {
            $roleWhere = " name LIKE '%$roleName%'";
            $roleWhere .= $this->is_cn ? ' AND is_cn = 1 ' : ' AND is_cn = 0 ';
            $roleList = M('Role')->where($roleWhere)->findAll();
            if (empty($roleList)) {
                return $this->display();
            }

            $roleIds = [];
            foreach ($roleList as $role) {
                $roleIds[] = $role['id'];
            }

            $where .= ' AND role_id IN ('.implode(',', $roleIds).')';
        }

        if ($admName = trim($_GET['adm_name'])) {
            $where .= ' AND adm_name LIKE "%'.$admName.'%"';
        }

        $effectStatus = $_GET['effect_status'];
        if (intval($effectStatus) != 0) {
            $effectStatus = $effectStatus < 0 ? 0 : $effectStatus;
            $where .= ' AND is_effect = ' . $effectStatus;
        }

        if (isset($_REQUEST['export'])) {
            ini_set('memory_limit', '1024M');

            $roleList = M('Role')->findAll();
            $roleMap = [];
            foreach ($roleList as $role) {
                $roleMap[$role['id']] = $role['name'];
            }

            $id = isset($_REQUEST[$this->pk_name]) ? trim($_REQUEST[$this->pk_name]) : "";
            if (!empty($id)) {
                $where = ' id IN ('.$id.')';
            }

            $list = M(MODULE_NAME)->where($where)->findAll();
            $header = ['编号', '姓名', '管理员账号', '管理员组', '状态'];
            foreach ($header as $key => $item) {
                $header[$key] = mb_convert_encoding($item, 'gbk', 'utf8');
            }

            header('Content-Type: application/vnd.ms-excel');
            header("Content-Disposition: attachment; filename=管理员列表.csv");
            header('Cache-Control: max-age=0');

            $fp = fopen('php://output', 'a');
            fputcsv($fp, $header);
            $count = 1; // 计数器
            $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
            $effectStatusMap = [
                0 => mb_convert_encoding('无效', 'gbk', 'utf8'),
                1 => mb_convert_encoding('有效', 'gbk', 'utf8')
            ];

            foreach ($list as $item) {
                $count++;
                if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $count = 0;
                }
                $line = [
                    $item['id'],
                    mb_convert_encoding($item['name'], 'gbk', 'utf8'),
                    mb_convert_encoding($item['adm_name'], 'gbk', 'utf8'),
                    mb_convert_encoding($roleMap[$item['role_id']], 'gbk', 'utf8'),
                    $effectStatusMap[$item['is_effect']]
                ];
                fputcsv($fp, $line);
            }
            return false;
        }
        if ($this->is_cn) {
            $allow_ids = str_replace('`',"\"",app_conf('CN_ADMIIN_ROLE_LIST'));
            $where .= ' AND id in ('.$allow_ids.') or is_cn = 1 ';
        }

        $this->_list(M(MODULE_NAME), $where);
        $this->assign('is_cn',$this->is_cn);
        $template = $this->is_cn ? 'index_cn':'index';
        $this->display($template);
    }

    public function trash()
    {
        $condition['is_delete'] = 1;
        $condition['is_cn'] = 1;
        $this->assign("default_map",$condition);
        parent::index();
    }
    public function add()
    {
        //输出分组列表

        $manage_group = app_conf('CN_ADMIIN_GROUP_LIST');
        if (!empty($manage_group)) {
            $where = $this->is_cn ? 'is_delete = 0 AND is_effect = 1 AND id in (' . $manage_group . ') or is_cn = 1 ' : "is_delete = 0 AND is_effect = 1";
        } else {
            $where = 'is_delete = 0 AND is_effect = 1';
        }
        $this->assign("role_list",M("Role")->where($where)->findAll());
        $this->assign("is_cn",$this->is_cn);
        $template = $this->is_cn ? 'add_cn' : 'add';
        $this->display($template);
    }
    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign ( 'vo', $vo );
        $manage_group = app_conf('CN_ADMIIN_GROUP_LIST');
        if (!empty($manage_group)) {
            $where = $this->is_cn ? 'is_delete = 0 AND is_effect = 1 AND id in ('.$manage_group.') or is_cn = 1' : "is_delete = 0 AND is_effect = 1";
        } else {
            $where = "is_delete = 0 AND is_effect = 1";
        }
        $this->assign("role_list",M("Role")->where($where)->findAll());
        $this->display ();
    }

    // 将管理员置为有效/无效
    public function set_effect() {
        $id          = intval($_REQUEST['id']);
        $ajax        = intval($_REQUEST['ajax']);
        $info        = M(MODULE_NAME)->where("id=" . $id)->getField("adm_adm_name");
        $c_is_effect = M(MODULE_NAME)->where("id=" . $id)->getField("is_effect"); //当前状态
        if (conf("DEFAULT_ADMIN") == $info) {
            $this->ajaxReturn($c_is_effect, l("DEFAULT_ADMIN_CANNOT_EFFECT"), $ajax);
        }
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $data        = array(
            "is_effect" => $n_is_effect,
        );
        $option      = array(
            "where" => "`id`='{$id}'",
        );
        if (M(MODULE_NAME)->save($data, $option) === false) {
            $status = 0;
        } else {
            $status = 1;
        }
        save_log($info . l("SET_EFFECT_" . $n_is_effect), $status);
        $this->ajaxReturn($n_is_effect, l("SET_EFFECT_" . $n_is_effect), $ajax);
    }

    public function insert() {
        B('FilterString');
        $data = M(MODULE_NAME)->create ();
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/add"));
        if(!check_empty($data['adm_name']))
        {
            $this->error(L("ADM_NAME_EMPTY_TIP"));
        }
        if(!check_empty($data['adm_password']))
        {
            $this->error(L("ADM_PASSWORD_EMPTY_TIP"));
        }
        if($data['role_id']==0)
        {
            $this->error(L("ROLE_EMPTY_TIP"));
        }
        if(M("Admin")->where("adm_name='".$data['adm_name']."'")->count()>0)
        {
            $this->error(L("ADMIN_EXIST_TIP"));
        }
        vendor('Psecio.Pwdcheck.Password');
        $pwdObj = new \Psecio\Pwdcheck\Password();
        $pwdObj->evaluate($data['adm_password']);
        if($pwdObj->getScore()<80){
            $this->error('密码强度不够');
        }
        // 更新数据
        $log_info = $data['adm_name'];
        $data['adm_password'] = md5(trim($data['adm_password']));
        $list=M(MODULE_NAME)->add($data);
        if (false !== $list) {
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
        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("adm_name");
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
        if(!check_empty($data['adm_password']))
        {
            unset($data['adm_password']);  //不更新密码
        }
        else
        {
            $data['adm_password'] = md5(trim($data['adm_password']));
            vendor('Psecio.Pwdcheck.Password');
            $pwdObj = new \Psecio\Pwdcheck\Password();
            $pwdObj->evaluate($data['adm_password']);
            if($pwdObj->getScore()<80){
                $this->error('密码强度不够');
            }
        }
        if($data['role_id']==0)
        {
            $this->error(L("ROLE_EMPTY_TIP"));
        }
        if(conf("DEFAULT_ADMIN")==$log_info)
        {
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $adm_name = $adm_session['adm_name'];
            if($log_info!=$adm_name)
            $this->error(l("DEFAULT_ADMIN_CANNOT_MODIFY"));

            if($data['is_effect']==0)
            {
                $this->error(l("DEFAULT_ADMIN_CANNOT_EFFECT"));
            }
        }
        // 更新数据
        $list=M(MODULE_NAME)->save ($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
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
                    $info[] = $data['adm_name'];
                    if(conf("DEFAULT_ADMIN")==$data['adm_name'])
                    {
                        $this->error ($data['adm_name'].l("DEFAULT_ADMIN_CANNOT_DELETE"),$ajax);
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
                    $info[] = $data['adm_name'];
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
                $rel_data = M(MODULE_NAME)->where($condition)->findAll();
                foreach($rel_data as $data)
                {
                    $info[] = $data['adm_name'];
                    if(conf("DEFAULT_ADMIN")==$data['adm_name'])
                    {
                        $this->error ($data['adm_name'].l("DEFAULT_ADMIN_CANNOT_DELETE"),$ajax);
                    }
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->delete();
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

    public function set_default()
    {
        return true; //禁用该功能
        $adm_id = intval($_REQUEST['id']);
        $admin = M("Admin")->getById($adm_id);
        if($admin)
        {
            M("Conf")->where("name = 'DEFAULT_ADMIN'")->setField("value",$admin['adm_name']);
            //开始写入配置文件
            $sys_configs = M("Conf")->findAll();
            $config_str = "<?php\n";
            $config_str .= "return array(\n";
            foreach($sys_configs as $k=>$v)
            {
                $config_str.="'".$v['name']."'=>'".addslashes($v['value'])."',\n";
            }
            $config_str.=");\n ?>";

            $filename = get_real_path()."public/sys_config.php";

            if (!$handle = fopen($filename, 'w')) {
                 $this->error(l("OPEN_FILE_ERROR").$filename);
            }


            if (fwrite($handle, $config_str) === FALSE) {
                 $this->error(l("WRITE_FILE_ERROR").$filename);
            }

            fclose($handle);


            save_log(l("CHANGE_DEFAULT_ADMIN"),1);
            clear_cache();
            $this->success(L("SET_DEFAULT_SUCCESS"));
        }
        else
        {
            $this->error(L("NO_ADMIN"));
        }
    }

}
?>
