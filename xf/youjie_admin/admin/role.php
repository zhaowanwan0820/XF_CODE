<?php

/**
 * ECSHOP 角色管理信息以及权限管理程序
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: wangleisvn $
 * $Id: role.php 16529 2009-08-12 05:38:57Z wangleisvn $
 */

define('IN_ECS', true);

require dirname(__FILE__) . '/includes/init.php';

/* act操作项的初始化 */
if (empty($_REQUEST['act'])) {
    $_REQUEST['act'] = 'login';
} else {
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/* 初始化 $exc 对象 */
$exc = new exchange($ecs->table("role"), $db, 'role_id', 'role_name');

/*------------------------------------------------------ */
//-- 退出登录
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'logout') {
    /* 清除cookie */
    setcookie('ECSCP[admin_id]', '', 1, null, null, null, true);
    setcookie('ECSCP[admin_pass]', '', 1, null, null, null, true);

    $sess->destroy_session();

    $_REQUEST['act'] = 'login';
}

/*------------------------------------------------------ */
//-- 登陆界面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'login') {
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    if ((intval($_CFG['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0) {
        $smarty->assign('gd_version', gd_version());
        $smarty->assign('random', mt_rand());
    }

    $smarty->display('login.htm');
}

/*------------------------------------------------------ */
//-- 角色列表页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'list') {
    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['admin_role']);
    $where = '1';
    if ($_SESSION['admin_type'] == 1) {
        $smarty->assign('action_link', array('href' => 'role.php?act=add&type=1', 'text' => $_LANG['admin_add_role']));
        $where .= ' and r.type = 1 and r.suppliers_id = ' . $_SESSION['suppliers_id'];
    } else {
        $smarty->assign('action_link', array('href' => 'role.php?act=add&type=0', 'text' => '添加平台角色', 'other' => [['href' => 'role.php?act=add&type=1', 'text' => '添加商家角色']]));
        $where .= ' and r.suppliers_id = 0';
    }
    $admin_list = get_role_list($where);
    foreach ($admin_list as &$v) {
        $v['type_format']        = $v['type'] == 1 ? '商家' : '平台';
        $v['create_time_format'] = date('Y-m-d H:i:s', $v['create_time']);
    }
    unset($v);
    $smarty->assign('full_page', 1);
    $smarty->assign('admin_list', $admin_list);
    $smarty->assign('admin_type', $_SESSION['admin_type']);

    /* 显示页面 */
    assign_query_info();
    $smarty->display('role_list.htm');
}

/*------------------------------------------------------ */
//-- 查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query') {
    $where = '1';
    if ($_SESSION['admin_type'] == 1) {
        $where .= ' and r.type = 1 and r.suppliers_id = ' . $_SESSION['suppliers_id'];
    } else {
        $where .= ' and r.suppliers_id = 0';
    }
    if (!empty($_POST['role_name'])) {
        $where .= " and r.role_name like '%{$_POST['role_name']}%'";
    }
    $admin_list = get_role_list($where);
    foreach ($admin_list as &$v) {
        $v['type_format']        = $v['type'] == 1 ? '商家' : '平台';
        $v['create_time_format'] = date('Y-m-d H:i:s', $v['create_time']);
    }
    unset($v);
    $smarty->assign('admin_list', $admin_list);
    $smarty->assign('admin_type', $_SESSION['admin_type']);

    make_json_result($smarty->fetch('role_list.htm'));
}

/*------------------------------------------------------ */
//-- 添加角色页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add') {
    /* 检查权限 */
    admin_priv('admin_manage');
    include_once ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/priv_action.php';

    //角色类型
    $type     = $_SESSION['admin_type'] == 1 ? 1 : ($_GET['type'] ?: 0);
    $priv_str = '';

    /* 获取权限的分组数据 */
    $sql_query = "SELECT action_id, parent_id, action_code, relevance ,`name` FROM " . $ecs->table('admin_action') .

        " WHERE parent_id = 0";
    $res = $db->query($sql_query);
    while ($rows = $db->FetchRow($res)) {
        $priv_arr[$rows['action_id']] = $rows;
    }

    /* 按权限组查询底级的权限名称 */
    $sql = "SELECT action_id, parent_id, action_code, relevance,`name` FROM " . $ecs->table('admin_action') .
    " WHERE parent_id " . db_create_in(array_keys($priv_arr));
    $result = $db->query($sql);
    while ($priv = $db->FetchRow($result)) {
        $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
    }

    // 将同一组的权限使用 "," 连接起来，供JS全选
    foreach ($priv_arr as $action_id => $action_group) {
        $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

        foreach ($action_group['priv'] as $key => $val) {
            $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
        }
    }

    /* 查询 */
    $sql = "SELECT suppliers_id, suppliers_name, suppliers_desc, is_check FROM " . $ecs->table("suppliers");
    $row = $db->getAll($sql);
    $smarty->assign('business', $row);

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['admin_add_role']);
    $smarty->assign('action_link', array('href' => 'role.php?act=list', 'text' => $_LANG['admin_list_role']));
    $smarty->assign('form_act', 'insert');
    $smarty->assign('action', 'add');
    $smarty->assign('lang', $_LANG);
    $smarty->assign('priv_arr', $priv_arr);
    //商家部分权限
    if ($type == 1) {
        $smarty->assign('priv_arr', get_suppliers_priv_list());
    }
    //管理员类型
    $smarty->assign('admin_type', $_SESSION['admin_type']);
    $smarty->assign('type', $type);

    /* 显示页面 */
    assign_query_info();
    $smarty->display('role_info.htm');
}

/*------------------------------------------------------ */
//-- 添加角色的处理
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert') {
    admin_priv('admin_manage');
    $act_list = @join(",", $_POST['action_code']);

    $type         = $_SESSION['admin_type'] == 1 ? 1 : ($_POST['type'] ?: 0);
    $suppliers_id = $_SESSION['admin_type'] == 1 ? $_SESSION['suppliers_id'] : 0;

    $sql = "select role_id from " . $ecs->table('role') . " where role_name = '" . trim($_POST['user_name']) . "' and type = {$type} and suppliers_id = '{$suppliers_id}'";
    $row = $db->getOne($sql);
    if (!empty($row)) {
        sys_msg($_LANG['add'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;已存在，请尝试其它名称", 1);
    }

    $sql = "INSERT INTO " . $ecs->table('role') . " (role_name, action_list, role_describe, type, suppliers_id, create_user_id, create_time) " .
    "VALUES ('" . trim($_POST['user_name']) . "','$act_list','" . trim($_POST['role_describe']) . "'," . $type . "," . $suppliers_id . ",{$_SESSION['admin_id']}," . time() . ")";

    $db->query($sql);
    /* 转入权限分配列表 */
    $new_id = $db->Insert_ID();

    /*添加链接*/

    $link[0]['text'] = $_LANG['admin_list_role'];
    $link[0]['href'] = 'role.php?act=list';

    sys_msg($_LANG['add'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $_LANG['action_succeed'], 0, $link);

    /* 记录管理员操作 */
    admin_log($_POST['user_name'], 'add', 'role');
}

/*------------------------------------------------------ */
//-- 编辑角色信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit') {
    include_once ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/priv_action.php';
    $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    /* 获得该管理员的权限 */
    $priv_str = $db->getOne("SELECT action_list FROM " . $ecs->table('role') . " WHERE role_id = '$_GET[id]'");

    /* 查看是否有权限编辑其他管理员的信息 */
    if ($_SESSION['admin_id'] != $_REQUEST['id']) {
        admin_priv('admin_manage');
    }

    /* 获取角色信息 */
    $sql = "SELECT role_id, role_name, role_describe, type FROM " . $ecs->table('role') .
        " WHERE role_id = '" . $_REQUEST['id'] . "'";
    if ($_SESSION['admin_type'] == 1) {
        $sql .= " and type = 1 and suppliers_id = {$_SESSION['suppliers_id']}";
    }
    $user_info = $db->getRow($sql);
    if (empty($user_info)) {
        sys_msg('角色不存在，无法编辑', 1);
    }

    /* 获取权限的分组数据 */
    $sql_query = "SELECT action_id, parent_id, action_code,relevance,`name` FROM " . $ecs->table('admin_action') .
        " WHERE parent_id = 0 and action_code != 'user_wd_platform'";
    $res = $db->query($sql_query);
    while ($rows = $db->FetchRow($res)) {
        $priv_arr[$rows['action_id']] = $rows;
    }

    /* 按权限组查询底级的权限名称 */
    $sql = "SELECT action_id, parent_id, action_code,relevance,`name` FROM " . $ecs->table('admin_action') .
    " WHERE parent_id " . db_create_in(array_keys($priv_arr));
    $result = $db->query($sql);
    while ($priv = $db->FetchRow($result)) {
        $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
    }
    // 将同一组的权限使用 "," 连接起来，供JS全选
    foreach ($priv_arr as $action_id => $action_group) {
        $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

        foreach ($action_group['priv'] as $key => $val) {
            $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
        }
    }
    /* 模板赋值 */

    $smarty->assign('user', $user_info);
    $smarty->assign('form_act', 'update');
    $smarty->assign('action', 'edit');
    $smarty->assign('ur_here', $_LANG['admin_edit_role']);
    $smarty->assign('action_link', array('href' => 'role.php?act=list', 'text' => $_LANG['admin_list_role']));
    $smarty->assign('lang', $_LANG);
    $smarty->assign('priv_arr', $priv_arr);
    $smarty->assign('user_id', $_GET['id']);
    $smarty->assign('type', $user_info['type']);
    $smarty->assign('admin_type', $_SESSION['admin_type']);
    //商家部分权限
    if ($user_info['type'] == 1) {
        $smarty->assign('priv_arr', get_suppliers_priv_list($user_info['role_id']));
    }

    assign_query_info();
    $smarty->display('role_info.htm');
}

/*------------------------------------------------------ */
//-- 更新角色信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update') {
    $type         = $_SESSION['admin_type'] == 1 ? 1 : ($_POST['type'] ?: 0);
    $suppliers_id = $_SESSION['admin_type'] == 1 ? $_SESSION['suppliers_id'] : 0;
    $sql          = "select role_id from " . $ecs->table('role') . " where role_name = '" . trim($_POST['user_name']) . "' and type = {$type} and suppliers_id = '{$suppliers_id}' and role_id != " . trim($_POST[id]);
    $row          = $db->getOne($sql);
    if (!empty($row)) {
        sys_msg($_LANG['edit'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;已存在，请尝试其它名称", 1);
    }
    /* 更新管理员的权限 */
    $act_list = @join(",", $_POST['action_code']);
    $sql      = "UPDATE " . $ecs->table('role') . " SET action_list = '$act_list', role_name = '" . $_POST['user_name'] . "', role_describe = '" . $_POST['role_describe'] . " ' " .
        "WHERE role_id = '$_POST[id]'";
    $db->query($sql);
    $user_sql = "UPDATE " . $ecs->table('admin_user') . " SET action_list = '$act_list' " .
        "WHERE role_id = '$_POST[id]'";
    $db->query($user_sql);
    /* 提示信息 */
    $link[] = array('text' => $_LANG['back_admin_list'], 'href' => 'role.php?act=list');
    sys_msg($_LANG['edit'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $_LANG['action_succeed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 删除一个角色
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove') {
    check_authz_json('admin_drop');

    $id         = intval($_GET['id']);
    $num_sql    = "SELECT count(*) FROM " . $ecs->table('admin_user') . " WHERE role_id = '$_GET[id]'";
    $remove_num = $db->getOne($num_sql);
    if ($remove_num > 0) {
        make_json_error($_LANG['remove_cannot_user']);
    } else {
        $exc->drop($id);
        $url = 'role.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    }

    ecs_header("Location: $url\n");
    exit;
}

/* 获取角色列表 */
function get_role_list($where = '')
{
    $list = array();
    if (!empty($where)) {
        $where = ' where ' . $where;
    }
    $sql = 'SELECT r.role_id, r.role_name, r.action_list, r.role_describe, r.type, r.suppliers_id, r.create_user_id, r.create_time, au.user_name create_user_name ' .
    'FROM ' . $GLOBALS['ecs']->table('role') . ' r left join ' . $GLOBALS['ecs']->table('admin_user') . ' au on r.create_user_id = au.user_id ' . $where . ' ORDER BY r.role_id DESC';
    $list = $GLOBALS['db']->getAll($sql);

    return $list;
}

//获取商家权限列表
function get_suppliers_priv_list($id = 0)
{
    $priv_str_suppliers = '';

    if (!empty($id)) {
        //获得该管理员的权限
        $priv_str_suppliers = $GLOBALS['db']->getOne("SELECT action_list FROM " . $GLOBALS['ecs']->table('role') . " WHERE role_id = {$id}");
    }

    /* 获取权限的分组数据 */
    $sql_query = "SELECT action_id, parent_id, action_code, relevance,`name` FROM " . $GLOBALS['ecs']->table('admin_action') .
        " WHERE parent_id = 0 and suppliers_can_use = 1";
    $res = $GLOBALS['db']->query($sql_query);
    while ($rows = $GLOBALS['db']->FetchRow($res)) {
        $priv_arr_suppliers[$rows['action_id']] = $rows;
    }

    /* 按权限组查询底级的权限名称 */
    $sql = "SELECT action_id, parent_id, action_code, relevance ,`name` FROM " . $GLOBALS['ecs']->table('admin_action') .
        " WHERE parent_id  > 0 and suppliers_can_use = 1 ";
    $result = $GLOBALS['db']->query($sql);
    while ($priv = $GLOBALS['db']->FetchRow($result)) {
        $priv_arr_suppliers[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
    }

    //商家权限 将同一组的权限使用 "," 连接起来，供JS全选
    foreach ($priv_arr_suppliers as $action_id => $action_group) {
        $priv_arr_suppliers[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

        foreach ($action_group['priv'] as $key => $val) {
            $priv_arr_suppliers[$action_id]['priv'][$key]['cando'] = (strpos($priv_str_suppliers, $val['action_code']) !== false || $priv_str_suppliers == 'all') ? 1 : 0;
        }
    }

    return $priv_arr_suppliers;
}
