<?php
/**
 * 调节账户申请
 * 张健
 * 2019-08-26
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/lib_order.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 调节账户申请列表
// 从ecshop中别的页面粘贴修改而成，分页JS是大坑，只能重写
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    admin_priv('account_change_audit');

    $list = account_list();

    /* 模板赋值 */
    if (!empty($_REQUEST['status'])) {
        $status = intval($_REQUEST['status']);
    } else {
        $status = -1;
    }
    $smarty->assign('ur_here',      $_LANG['11_account_change_audit']);
    $smarty->assign('status',       $status);
    $smarty->assign('list',         $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);
    $smarty->assign('full_page',    1);

    assign_query_info();
    $smarty->display('account_change_audit_list.htm');
}

// 审核页面
else if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('account_change_audit_edit');

    if (!empty($_REQUEST['id'])) {
        $id = intval($_REQUEST['id']);
    } else {
        $id = 0;
    }
    $sql   = "SELECT * FROM " . $GLOBALS['ecs']->table('account_change_audit') . " where audit_id = {$id} LIMIT 1";
    $audit = $GLOBALS['db']->getAll($sql);
    if (!$audit) {
        sys_msg($_LANG['no_audit']);
    } else {
        $audit = $audit[0];
    }
    $user_id = empty($audit['user_id']) ? 0 : intval($audit['user_id']);
    if ($user_id <= 0)
    {
        sys_msg('invalid param');
    }
    $user = user_info($user_id);
    if (empty($user))
    {
        sys_msg($_LANG['user_not_exist']);
    }
    $sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('admin_user') . " where user_id = {$audit['admin_user_id']}";
    $admin_user = $GLOBALS['db']->getOne($sql);

    $audit['user_money_abs']   = abs($audit['user_money']);
    $audit['frozen_money_abs'] = abs($audit['frozen_money']);
    $audit['rank_points_abs']  = abs($audit['rank_points']);
    $audit['pay_points_abs']   = abs($audit['pay_points']);
    $audit['created_at']       = date('Y-m-d H:i:s', $audit['created_at']);

    $smarty->assign('user', $user);
    $smarty->assign('admin_user', $admin_user);
    $smarty->assign('audit', $audit);
    $smarty->assign('ur_here', $_LANG['add_account']);
    $smarty->assign('action_link', array('href' => 'account_change_audit.php?act=list', 'text' => $_LANG['account_list']));
    assign_query_info();
    $smarty->display('account_change_audit_edit.htm');
}

// 执行审核操作
else if ($_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('account_change_audit_edit');

    $token=trim($_POST['token']);
    if($token!=$_CFG['token'])
    {
        sys_msg($_LANG['no_account_change'], 1);
    }

    /* 检查参数 */
    if (!empty($_REQUEST['audit_id'])) {
        $id = intval($_REQUEST['audit_id']);
    } else {
        $id = 0;
    }
    $sql   = "SELECT * FROM " . $GLOBALS['ecs']->table('account_change_audit') . " where audit_id = {$id} LIMIT 1";
    $audit = $GLOBALS['db']->getAll($sql);
    if (!$audit) {
        sys_msg($_LANG['no_audit']);
    } else {
        $audit = $audit[0];
    }
    $user_id = empty($audit['user_id']) ? 0 : intval($audit['user_id']);
    if ($user_id <= 0)
    {
        sys_msg('invalid param');
    }
    $user = user_info($user_id);
    if (empty($user))
    {
        sys_msg($_LANG['user_not_exist']);
    }

    /* 提交值 */
    $audit_desc     = sub_str($_REQUEST['audit_desc'], 255, false);
    $change_desc    = sub_str($audit['change_desc'], 255, false);
    $user_money     = floatval($audit['user_money']);
    $frozen_money   = floatval($audit['frozen_money']);
    $rank_points    = floatval($audit['rank_points']);
    $pay_points     = floatval($audit['pay_points']);

    if ($user_money == 0 && $frozen_money == 0 && $rank_points == 0 && $pay_points == 0)
    {
        sys_msg($_LANG['no_account_change']);
    }

    $time = time();
    if ($_REQUEST['audit_status'] == 2) {
        $GLOBALS['db']->query('START TRANSACTION');

        $sql_update = "UPDATE " . 
            $GLOBALS['ecs']->table('account_change_audit') . 
            " SET audit_status = 2, audit_desc = '" . 
            $audit_desc . 
            "', audit_time = " . 
            $time . 
            " where audit_id = {$id}";
        $update = $GLOBALS['db']->query($sql_update);

        /* 保存 */
        // itz_account_log 新增
        if(($res = log_account_change_transaction(
            $user_id, 
            $user_money, 
            $frozen_money, 
            $rank_points, 
            $pay_points, 
            $change_desc, 
            ACT_ADJUSTING
        )) !== true) {
            $GLOBALS['db']->query('ROLLBACK');
            sys_msg($res);
        }

        // 如果是线下退浣币类型则扣除对应商家浣币（供应商扣除平台账户浣币）
        if ($audit['type'] == ACCOUNT_CHANGE_AUDIT_OFFLINE_HUANBI) {
            $sql = 'SELECT o.order_id, o.suppliers_id, o.is_huanbi_settlement, s.cooperate_type FROM ' . 
                $GLOBALS['ecs']->table('order_info') . ' AS o LEFT JOIN ' .
                $GLOBALS['ecs']->table('suppliers') . ' AS s ON o.suppliers_id = s.suppliers_id ' .
                ' WHERE o.order_id = ' . $audit['related_info'];
            if (!$orderModel = $GLOBALS['db']->getRow($sql)) {
                $GLOBALS['db']->query('ROLLBACK');
                sys_msg('未找到关联订单');
            }
            if(
                ($orderModel['cooperate_type'] == COO_RESIDENT && $orderModel['is_huanbi_settlement'] == HUANBI_SETTLEMENT) && 
                ($res = suppliers_account_change(
                    $orderModel['suppliers_id'],
                    $orderModel['order_id'],
                    'offline_huanbi_return',
                    '订单线下退积分',
                    2,
                    -$user_money
                )) !== true
            ) {
                $GLOBALS['db']->query('ROLLBACK');
                sys_msg($res);
            }
            if(
                ($orderModel['cooperate_type'] == COO_SUPPLIERS || $orderModel['is_huanbi_settlement'] == HUANBI_UNSETTLEMENT) && 
                ($res = platform_account_change(
                        0,
                        -$user_money,
                        CT_HUANBI_OFFLINE,
                        2,
                        $orderModel['order_id'],
                        '订单线下退积分',
                        $orderModel['suppliers_id']
                )) !== true
            ){
                $GLOBALS['db']->query('ROLLBACK');
                sys_msg($res);
            }
        }
        
        $GLOBALS['db']->query('COMMIT');

        /* 提示信息 */
        $links = array(
            array('href' => 'account_change_audit.php?act=list', 'text' => $_LANG['account_list'])
        );
        sys_msg($_LANG['log_account_change_ok'], 0, $links);
    } else {
        $sql_update = "UPDATE " . $GLOBALS['ecs']->table('account_change_audit') . " SET audit_status = 3, audit_desc = '" . $audit_desc . "', audit_time = " . $time . " where audit_id = {$id}";
        $update = $GLOBALS['db']->query($sql_update);

        /* 提示信息 */
        $links = array(
            array('href' => 'account_change_audit.php?act=list', 'text' => $_LANG['account_list'])
        );
        sys_msg($_LANG['no_account_change'], 0, $links);
    }
}

// 详情页面
else if ($_REQUEST['act'] == 'info')
{
    if (!empty($_REQUEST['id'])) {
        $id = intval($_REQUEST['id']);
    } else {
        $id = 0;
    }
    $sql   = "SELECT * FROM " . $GLOBALS['ecs']->table('account_change_audit') . " where audit_id = {$id} LIMIT 1";
    $audit = $GLOBALS['db']->getAll($sql);
    if (!$audit) {
        sys_msg($_LANG['no_audit']);
    } else {
        $audit = $audit[0];
    }
    $user_id = empty($audit['user_id']) ? 0 : intval($audit['user_id']);
    if ($user_id <= 0)
    {
        sys_msg('invalid param');
    }
    $user = user_info($user_id);
    if (empty($user))
    {
        sys_msg($_LANG['user_not_exist']);
    }
    $sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('admin_user') . " where user_id = {$audit['admin_user_id']}";
    $admin_user = $GLOBALS['db']->getOne($sql);

    $audit['user_money_abs']   = abs($audit['user_money']);
    $audit['frozen_money_abs'] = abs($audit['frozen_money']);
    $audit['rank_points_abs']  = abs($audit['rank_points']);
    $audit['pay_points_abs']   = abs($audit['pay_points']);
    $audit['created_at']       = date('Y-m-d H:i:s', $audit['created_at']);
    $audit['audit_time']       = date('Y-m-d H:i:s', $audit['audit_time']);

    $smarty->assign('user', $user);
    $smarty->assign('admin_user', $admin_user);
    $smarty->assign('audit', $audit);
    $smarty->assign('ur_here', $_LANG['audit_info']);
    $smarty->assign('action_link', array('href' => 'account_change_audit.php?act=list', 'text' => $_LANG['account_list']));
    assign_query_info();
    $smarty->display('account_change_audit_info.htm');
}

function account_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤列表 */
        $filter['user_id']   = empty($_REQUEST['user_id']) ? '' : trim($_REQUEST['user_id']);
        $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);

        $filter['status'] = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : -1;

        $where = " ON a.admin_user_id = au.user_id  ";
        if ($filter['status'] != -1)
        {
            $where .= " AND a.audit_status = '$filter[status]' ";
        }
        if ($filter['user_id'])
        {
            $where .= " AND a.user_id = ". $filter['user_id'] . " ";
        }
        if ($filter['user_name'])
        {
            $where .= " AND u.user_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%' ";
        }
        $sql_count = "SELECT COUNT(*) FROM ( " .$GLOBALS['ecs']->table('account_change_audit'). " AS a INNER JOIN " . $GLOBALS['ecs']->table('users') . " AS u ON a.user_id = u.user_id ) INNER JOIN " . $GLOBALS['ecs']->table('admin_user') . " AS au " . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql_count);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $page   = intval($_REQUEST['page']);
        if ($page < 1) {
            $page = 1;
        }
        $start  = ($page - 1) * $filter['page_size'];

        /* 查询数据 */
        $sql  = "SELECT a.*, u.user_name, au.user_name AS admin_user FROM ( " . $GLOBALS['ecs']->table('account_change_audit'). " AS a INNER JOIN " . $GLOBALS['ecs']->table('users'). " AS u ON a.user_id = u.user_id ) INNER JOIN " . $GLOBALS['ecs']->table('admin_user') . " AS au " . $where . "ORDER BY a.audit_id DESC LIMIT " . $start . " , " . $filter['page_size'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $audit_status[1] = '待审核';
    $audit_status[2] = '审核通过';
    $audit_status[3] = '审核未通过';

    $list = $GLOBALS['db']->getAll($sql);
    foreach ($list AS $key => $value)
    {
        $list[$key]['user_money']   = "￥ {$value['user_money']}元";
        $list[$key]['frozen_money'] = "￥ {$value['frozen_money']}元";
        $list[$key]['created_at']   = date('Y-m-d H:i:s',$value['created_at']);
        $list[$key]['status']       = $audit_status[$value['audit_status']];
     }
    $arr = array('list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

?>