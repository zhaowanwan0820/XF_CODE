<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 用户帐号列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{

    /* 检查权限 */
    admin_priv('debt_list');
    $smarty->assign('ur_here',      $_LANG['12_user_debt_list']);

    $list = debt_list();

    $smarty->assign('status_list',      status_list());
    $smarty->assign('is_rollback_list', is_rollback_list());
    $smarty->assign('list',         $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);
    $smarty->assign('full_page',    1);
    $smarty->assign('sort_user_id', '<img src="images/sort_desc.png">');
    $smarty->assign('pageHtml',     'debt_list.htm');
    assign_query_info();
    $smarty->display('user_debt_list.htm');
}

/*------------------------------------------------------ */
//-- ajax返回用户列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    admin_priv('debt_list');
    $list = debt_list();

    $smarty->assign('list',         $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);
    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('user_debt_list.htm'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}


function status_list() {
    return array(
        '1' => array('id'=>'1', 'name' => '兑换中',     'style'=>'style="color:blue;"'),
        '2' => array('id'=>'2', 'name' => '兑换成功',   'style'=>'style="color:green;"',    'check'=> '1'),
        '3' => array('id'=>'3', 'name' => '兑换失败',   'style'=>'style="color:red;"'),
    );
}


function is_rollback_list() {
    return array(
        '0' => array('id'=>'0', 'name' => '否', 'style'=>'style="color:red;"'),
        '1' => array('id'=>'1', 'name' => '是', 'style'=>'style="color:green;"'),
    );
}


/**
 * 债权列表
 * @Author   haofuheng
 * @DateTime 2019-09-29T10:45:39+0800
 * @return   [type]                   [description]
 */
function debt_list()
{
    $result = get_filter();
    if ($result === false)
    {

        $filter['sort_by']      = empty($_REQUEST['sort_by'])    ? 'deo.order_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order']   = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);
        $sql_from               = " FROM " . $GLOBALS['ecs']->table('debt_order') . ' deo,' . $GLOBALS['ecs']->table('users'). ' u'.
                                    ' where deo.user_id = u.user_id';
        $sql_where              = '';

        // 1.用户名
        $filter['user_name']    = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
        if (!empty($filter['user_name'])) {
            $sql_where           .= " and u.user_name like '%".$filter['user_name']."%'";
        }

        // 2.手机号
        $filter['mobile_phone']     = empty($_REQUEST['mobile_phone']) ? '' : trim($_REQUEST['mobile_phone']);
        if (!empty($filter['mobile_phone'])) {
            $sql_where              .= " and u.mobile_phone like '%".$filter['mobile_phone']."%'";
        }

        // 3.user_id
        $filter['user_id']          = empty($_REQUEST['user_id']) ? '' : trim($_REQUEST['user_id']);
        if (!empty($filter['user_id'])) {
            $sql_where              .= " and u.user_id = '".$filter['user_id']."'";
        }

        // 4.status
        $filter['status']          = empty($_REQUEST['status']) ? '2' : trim($_REQUEST['status']);
        if (in_array($filter['status'], [1,2,3])) {
            $sql_where              .= " and deo.status = '".$filter['status']."'";
        }

        // 4.is_rollback
        if (isset($_REQUEST['is_rollback'])) {
            $filter['is_rollback']      = $_REQUEST['is_rollback'];
            if (in_array($filter['is_rollback'], [0,1])) {
                $sql_where              .= " and deo.is_rollback = '".$filter['is_rollback']."'";
            }
        }


        $filter['record_count'] = $GLOBALS['db']->getOne('SELECT COUNT(*) '.$sql_from.$sql_where);

        /* 分页大小 */
        $filter         = page_and_size($filter);
        $sql_limit      = " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                            " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
        $sql = 'select deo.*,u.user_name,u.user_id,u.mobile_phone'.$sql_from.$sql_where.$sql_limit;

        // echo $sql;

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    // echo "<pre>";
    // echo $sql;
    $list = $GLOBALS['db']->getAll($sql);

    $debt_order_id_arr  = array();
    foreach ($list as $key => $value) {

        if (!empty($value['order_id'])) {
            $debt_order_id_arr[] = $value['order_id'];
        }

        $value['format_detail']     = '';
        if (!empty($value['detail'])) {
            $detail     = json_decode($value['detail'], true);
            $html   = '';
            if (!empty($detail['account'])) {
                $html   .= '总额：'.$detail['account'];
            }
            if (!empty($detail['detail'])) {
                foreach ($detail['detail'] as $key2 => $value2) {
                    $html   .= '<br/> '.$value2['name'].' ：'.$value2['account'];
                }
            }
            $value['format_detail']     = $html;
        }


        $value['format_is_rollback']            = '';
        $is_rollback_list           = is_rollback_list();
        if (isset($is_rollback_list[$value['is_rollback']])) {
            $value['format_is_rollback']        = $is_rollback_list[$value['is_rollback']]['name'];
            $value['format_is_rollback_style']  = $is_rollback_list[$value['is_rollback']]['style'];
        }

        $value['format_status'] = '';
        $status_list            = status_list();
        if (!empty($value['status']) && isset($status_list[$value['status']])) {
            $value['format_status']         = $status_list[$value['status']]['name'];
            $value['format_status_style']   = $status_list[$value['status']]['style'];
        }

        $value['format_createtime'] = local_date($GLOBALS['_CFG']['time_format'], $value['createtime']);

        $list[$key]     = $value;
    }

    if (!empty($debt_order_id_arr)) {
        $sql    = 'select order_sn,debt_id from '.$GLOBALS['ecs']->table('order_info').' where debt_id in ('.implode(",", $debt_order_id_arr).')';
        $order_info_list = $GLOBALS['db']->getAll($sql);
        $order_info_list_new    = array();
        foreach ($order_info_list as $key => $value) {
            $order_info_list_new[$value['debt_id']]    = $value;
        }
        $order_info_list    = $order_info_list_new;
        foreach ($list as $key => $value) {
            $value['order_sn']      = '';
            if (!empty($order_info_list[$value['order_id']])) {
                $value['order_sn']  = $order_info_list[$value['order_id']]['order_sn'];
            }
            $list[$key]     = $value;
        }

    }

    $arr = array(
        'list'          => $list,
        'filter'        => $filter,
        'page_count'    => $filter['page_count'],
        'record_count' => $filter['record_count']
    );

    return $arr;
}

?>