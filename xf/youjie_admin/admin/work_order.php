<?php
/**
 * 工单
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 工单列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('work_order_list');
    if (!empty($_GET['status'])) {
    	$status = $_GET['status'];
    } else {
    	$status = 'ALL';
    }
    $smarty->assign('status',$status);
    $smarty->display('work_order_list.htm');
}



