<?php

/**
 * ECSHOP 管理中心
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
define('SUPPLIERS_ACTION_LIST', 'delivery_view,back_view');
/*------------------------------------------------------ */
//-- 换豆列表 | 平台浣豆信息
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'user_wd_index') {
    /* 检查权限 */
    admin_priv('user_wd_index');
    $smarty->display('user_wd_index.htm');
}
elseif ($_REQUEST['act'] == 'user_wd_add_deit') {
    /* 检查权限 */
    admin_priv('user_wd_add_deit');
    $smarty->display('user_wd_add_deit.htm');

}
elseif ($_REQUEST['act'] == 'user_wd_use_detail') {
    /* 检查权限 */
    admin_priv('user_wd_use_detail');
    $smarty->display('user_wd_use_detail.htm');

}
elseif ($_REQUEST['act'] == 'user_wd_use_person') {
    /* 检查权限 */
    admin_priv('user_wd_use_person');
    $smarty->display('user_wd_use_person.htm');

}

