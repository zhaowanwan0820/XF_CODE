<?php
/**
 *分销管理
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 活动列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'activityList')
{
     /* 检查权限 */
     admin_priv('mlm_activity_list');
     $smarty->display('RetailActivityList.htm');
}
/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'goodsList')
{
    /* 检查权限 */
    admin_priv('mlm_goods_list');
    $smarty->display('RetailSupplierList.htm');
}
/*------------------------------------------------------ */
//-- 数据看板
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'dashboard')
{
    /* 检查权限 */
    admin_priv('mlm_dashboard');
    $smarty->display('Distributiondata.htm');
}



