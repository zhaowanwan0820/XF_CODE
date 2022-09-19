<?php

/**
 * ECSHOP 商品管理程序
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: goods.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');

$time         = time();
$home_area    = [6 => 805, 7 => 808, 8 => 807, 9 => 803, 10 => 810, 11 => 804, 12 => 809]; //首页专区
/*
 * 专区列表
 */
if ($_REQUEST['act'] == 'home_tags_list') {
    admin_priv('home_goods'); // 检查权限
    $sql = "SELECT t.*,a.user_name FROM ".$ecs->table('goods_tags_fields')." t LEFT JOIN ".$ecs->table('admin_user')." a on t.user_id = a.user_id";
    $rows = $db->query($sql);
    while ($row = $rows->fetch_assoc()) {
        $row['url'] = 'https://m.youjiemall.com/h5/#/products?admin_order=1&tags_id=' . $row['id'];
        $tags[]     = $row;

    }
    $smarty->assign('tags', $tags);
    $smarty->assign('action_link', array('href' => 'goods_home.php?act=add_tags', 'text' => $_LANG['add_tags']));
    $smarty->assign('ur_here', $_LANG['goods_cat_plate']);


    assign_query_info();
    $smarty->display('home_cat.htm');
}

/*
 * 删除专区
 */
/*elseif ($_REQUEST['act'] == 'del_tags') {
    admin_priv('home_goods'); // 检查权限
    $tags_id = !empty($_REQUEST['tags_id']) ? intval($_REQUEST['tags_id']) : '';

}*/

/*
 * 添加/编辑
 */
elseif ($_REQUEST['act'] == 'add_tags' || $_REQUEST['act'] == 'edit_tags') {
    admin_priv('home_goods'); // 检查权限

    $is_add  = $_REQUEST['act'] == 'add_tags' ? true : false;
    $tags_id = isset($_REQUEST['tags_id']) ? $_REQUEST['tags_id'] : 0;
    $tags    = ['id' => 0, 'tags_name' => '',];
    $goods   = [];

    if ($is_add) {
        $smarty->assign('form_action', 'insert_tags');
        $smarty->assign('ur_here', $_LANG['add_tags']);
        $smarty->assign('appoint_debt', true);
    } else {
        $sql = "SELECT * FROM " . $ecs->table('goods_tags_fields') . "  WHERE id = {$tags_id}";
        $tags = $db->getRow($sql);
        if (!empty($tags['zx_borrow_ids']) || !empty($tags['ph_borrow_ids'])) {
            $tags['appoint'] = 1;
        }
        $tags['zx_borrow_ids'] = str_replace(',', PHP_EOL,$tags['zx_borrow_ids']);
        $tags['ph_borrow_ids'] = str_replace(',', PHP_EOL,$tags['ph_borrow_ids']);
        $is_new = true;
        $hh_newbie = 0;
        $cat_ids = '';
//        if (array_key_exists($tags_id,$home_area)) {
//            $cat_ids   = getAllByCatId($home_area[$tags_id]);
//            array_unshift($cat_ids, $home_area[$tags_id]);
//            $cat_ids   = trim(implode(',', $cat_ids));
//        }
        $sql  = getHomeAreaSql($cat_ids, $tags_id, $is_new, $hh_newbie, $home_area);
        $sql .= getHomeAreaOrderBy($tags_id, $home_area);
        $goods = $db->getAll($sql);

        if (empty($tags['zx_borrow_ids']) && empty($tags['ph_borrow_ids'])) {
            $smarty->assign('appoint_debt', true);
        }
        $smarty->assign('form_action', 'update_tags');
        $smarty->assign('ur_here', $_LANG['edit_tags']);
    }

    $smarty->assign('goods',$goods);
    $smarty->assign('tags', $tags);
    $smarty->assign('tags_id',$tags_id);
    $smarty->assign('action_link', array('href' => 'goods_home.php?act=home_tags_list', 'text' => $_LANG['goods_cat_plate']));

    assign_query_info();
    $smarty->display('home_tags_info.htm');
}


/*
 *  插入/更新
 */
elseif ($_REQUEST['act'] == 'insert_tags' || $_REQUEST['act'] == 'update_tags') {
    admin_priv('home_goods'); // 检查权限

    $admin_id  = $_SESSION['admin_id'];
    $is_insert = $_REQUEST['act'] == 'insert_tags' ? true : false;
    $action    = $_REQUEST['act'] == 'insert_tags' ? 'home_add' : 'home_edit';

    $tags_name = !empty($_REQUEST['tags_name']) ? htmlspecialchars(trim($_REQUEST['tags_name'])) : '';
    $tags_id   = !empty($_REQUEST['tags_id']) ? intval($_REQUEST['tags_id']) : 0;
    $post_ids  = !empty($_REQUEST['ids']) ? htmlspecialchars(trim($_REQUEST['ids'])) : '';

    //指定债权
    $appoint   = !empty($_POST['appoint']) ? intval($_POST['appoint']) : 0;
    $zx_borrow = !empty($_POST['zx_borrow']) ? htmlspecialchars(trim($_POST['zx_borrow'])) : '';
    $ph_borrow = !empty($_POST['ph_borrow']) ? htmlspecialchars(trim($_POST['ph_borrow'])) : '';


    if (empty($tags_name)) {
        sys_msg('请填写专区名称');
    }
    if (!empty($post_ids)) {
        $arr       = explode(PHP_EOL,$post_ids);
        $post_ids  = array_values(array_filter($arr));
    }

    $db->query('START TRANSACTION');
    $insert_str = [];
    $sort_str   = '';
    $sort_order = 100;

    //指定债权
    if ($appoint) {
        if (!empty($zx_borrow)) $zx_borrow = str_replace(PHP_EOL,',',$zx_borrow);
        if (!empty($ph_borrow)) $ph_borrow = str_replace(PHP_EOL,',',$ph_borrow);
    } else {
        $zx_borrow = $ph_borrow = '';
    }

    if ($appoint && !$is_insert) {
        $sql = "SELECT * FROM " . $ecs->table('goods_tags_fields') . "  WHERE id = {$tags_id}";
        $tags_fields = $db->getRow($sql);
        if (!empty($tags_fields['zx_borrow_ids']) || !empty($tags_fields['ph_borrow_ids'])) {
            $zx_borrow = $tags_fields['zx_borrow_ids'];
            $ph_borrow = $tags_fields['ph_borrow_ids'];
        }
    }

    //新增/更新专区名称
    if ($is_insert) {
        $sql = "INSERT into " . $ecs->table('goods_tags_fields') . "(id,tags_name,user_id,start_at,close_at,created_at,updated_at,zx_borrow_ids,ph_borrow_ids)" .
            " VALUE(null,'{$tags_name}',{$admin_id},0,0,{$time},{$time},'{$zx_borrow}','{$ph_borrow}')";
        if (!$db->query($sql)) {
            $db->query('ROLLBACK');
            sys_msg('添加专区失败');
        }
        $tags_id = $db->insert_id();
    } else {
        $sql = "UPDATE " . $ecs->table('goods_tags_fields') . "SET tags_name = '{$tags_name}',zx_borrow_ids = '{$zx_borrow}',ph_borrow_ids = '{$ph_borrow}' WHERE id = {$tags_id}";
        if (!$db->query($sql)) {
            $db->query('ROLLBACK');
            sys_msg('更新专区名称失败');
        }

        // 指定债权同步到商品
        $sql = "select goods_id from " . $ecs->table('goods_tags') . " where tags_id = {$tags_id} 
            AND deleted_at = 0 AND (start_at < {$time}) AND (close_at = 0 OR close_at > {$time})";
        $data = $db->getAll($sql);
        $goods_upd = array_column($data, 'goods_id');
        if (!empty($goods_upd)) {
            if ($appoint) {
                $appoint_do = "UPDATE " . $ecs->table('goods') . "SET zx_borrow_ids = '{$zx_borrow}',ph_borrow_ids = '{$ph_borrow}' where " . db_create_in($goods_upd, 'goods_id');
                if (!$db->query($appoint_do)) {
                    $db->query('ROLLBACK');
                    sys_msg('指定债权商品修改失败');
                }
            } else {
                $appoint_no = "UPDATE " . $ecs->table('goods') . "SET zx_borrow_ids = '',ph_borrow_ids = '' where " . db_create_in($goods_upd, 'goods_id');
                if (!$db->query($appoint_no)) {
                    $db->query('ROLLBACK');
                    sys_msg('指定债权商品修改失败');
                }
            }
        }
    }

    //为专区添加商品
    if (!empty($post_ids)) {
        //验证是否重复
        $sql = "select g.goods_id,g.tags_id,f.id,f.tags_name from " . $ecs->table('goods_tags') . " as g left join " .
            $ecs->table('goods_tags_fields') . ' as f on g.tags_id = f.id where g.deleted_at = 0 and ' . db_create_in($post_ids,'goods_id') .'limit 1';
        $return = $db->getRow($sql);
        if (!empty($return)) {
            $db->query('ROLLBACK');
            sys_msg($return['goods_id'].'商品已存在：' . $return['tags_name'] . '专区');
        }

        //验证商品状态
        $sql  = "SELECT goods_id FROM " . $ecs->table('goods') . " WHERE ".db_create_in($post_ids,'goods_id') . " and sale_type = 0 and is_delete = 0 and hh_newbie = 0 and cat_id <> 3 and zx_borrow_ids = '' and ph_borrow_ids = ''";
        if (array_key_exists($tags_id,$home_area)) {
            $sql .= ' and is_on_sale = 1 and is_check = 2';
        }
        $data = $db->getAll($sql);
        $ids  = array_column($data,'goods_id');
        $error_ids = $post_ids;
        foreach ($error_ids as $key => $val) {
            if(in_array(intval($val),$ids)) {
                unset($error_ids[$key]);
            }
        }
        if (!empty($error_ids)) {
            $db->query('ROLLBACK');
            sys_msg('商品状态不符合：' . trim(implode(',',$error_ids),','));
        }
        if (count($data) != count($post_ids)) {
            $db->query('ROLLBACK');
            sys_msg('商品类型错误或不存在');
        }

        //准备添加sql
        foreach ($post_ids as $key => $val) {
            $insert_str[] = "({$val},{$tags_id},{$admin_id},{$time},{$time},0)";
            $sort_str    .= " WHEN {$val} THEN {$sort_order}";
        }

        $insert_sql = "INSERT INTO " . $ecs->table('goods_tags') . "(`goods_id`, `tags_id`, `user_id`, `created_at`, `updated_at`, `deleted_at`) values".  trim(implode(',', $insert_str), ',');
        if (!$db->query($insert_sql)) {
            $db->query('ROLLBACK');
            sys_msg('添加专区商品失败');
        }

        //专区商品修改排序值
        $update_sql = "UPDATE " . $ecs->table('goods') . "SET sort_order = case goods_id " . $sort_str . " else sort_order end where " . db_create_in($post_ids, 'goods_id');
        if (!$db->query($update_sql)) {
            $db->query('ROLLBACK');
            sys_msg('添加专区商品失败');
        }

        if ($appoint && (!empty($zx_borrow) || !empty($ph_borrow))) {
            $appoint_sql = "UPDATE " . $ecs->table('goods') . " SET zx_borrow_ids = '{$zx_borrow}',ph_borrow_ids = '{$ph_borrow}' where " . db_create_in($post_ids, 'goods_id');
            if (!$db->query($appoint_sql)) {
                $db->query('ROLLBACK');
                sys_msg('指定债权失败');
            }
            $sql = 'UPDATE ' . $ecs->table('goods_tags') . " SET deleted_at = {$time},updated_at = {$time} where tags_id <> {$tags_id} and " . db_create_in($post_ids, 'goods_id');
            if (!$db->query($sql)) {
                $db->query('ROLLBACK');
                sys_msg('商品在其他专区清除失败');
            }
        }
    }

    $db->query('COMMIT');
    admin_log($tags_name, $action, 'goods_tags');
    $link = [
        ['href' => "goods_home.php?act=edit_tags&tags_id=$tags_id", 'text' => '继续添加'],
        ['href' => 'goods_home.php?act=home_tags_list',             'text' => '商品专区列表'],
    ];
    sys_msg($is_insert ? $_LANG['add_tags_ok'] : $_LANG['edit_tags_ok'], 0, $link);
}

/*
 * 移除商品
 */
elseif ($_REQUEST['act'] == 'del_goods_tags') {
    check_authz_json('home_goods');
    $goods_id = intval($_REQUEST['goods_id']);
    $tags_id  = intval($_REQUEST['tags_id']);
    $now = gmtime();
    $db->query('START TRANSACTION');
    $sql = "UPDATE " . $ecs->table('goods_tags') . " set deleted_at = {$now} where tags_id = {$tags_id} and goods_id = {$goods_id} and deleted_at = 0 limit 1";
    if (!$db->query($sql)) {
        $db->query('ROLLBACK');
        make_json_error();
    }
    $sql = "select goods_id,zx_borrow_ids,ph_borrow_ids from " . $ecs->table('goods') . " where goods_id = {$goods_id} limit 1";
    $data = $db->getRow($sql);
    if (!empty($data['zx_borrow_ids']) || !empty($data['ph_borrow_ids'])) {
        $sql = "UPDATE " . $ecs->table('goods') . "SET zx_borrow_ids = '',ph_borrow_ids = '' WHERE goods_id = {$goods_id}";
        if (!$db->query($sql)) {
            $db->query('ROLLBACK');
            make_json_error();
        }
    }
    $db->query('COMMIT');
    make_json_result();
}

/*
 * 更新排序值
 */
elseif ($_REQUEST['act'] == 'edit_sort_order') {
    check_authz_json('home_goods');

    $goods_id   = intval($_POST['id']);
    $sort_order = intval($_POST['val']) > 0 ? intval($_POST['val']) : 1;

    $update_sql = "UPDATE " . $ecs->table('goods') . " SET sort_order ={$sort_order} where goods_id = {$goods_id}";

    if ($db->query($update_sql)) {
        clear_cache_files();
        make_json_result($sort_order);
    }

    make_json_error();
}


/*
 *  获取以物抵债商家
 */
function getDebtSuppliers()
{
    $sql = "SELECT suppliers_id FROM ".$GLOBALS['ecs']->table('suppliers')." WHERE type in(1,3)";
    $suppliersModel = $GLOBALS['db']->getAll($sql);
    $suppliers_arr  = array_column($suppliersModel, 'suppliers_id');
    $suppliers_str  = implode(',', $suppliers_arr);
    return $suppliers_str;
}

/*
 *  获取子分类
 */
function getAllByCatID($cat_id)
{
    $arr = [];
    if (is_array($cat_id)) {
        $cat_id = implode(',', $cat_id);
    }
    $sql = "select cat_id from ".$GLOBALS['ecs']->table('category')." where parent_id in({$cat_id}) and is_show = 1";
    $categoryModel = $GLOBALS['db']->getAll($sql);
    if (!empty($categoryModel)) {
        $cat_id_arr = array_column($categoryModel, 'cat_id');
        $arr        = array_merge($arr, $cat_id_arr);
        $arr        = array_merge($arr, getAllByCatId($cat_id_arr));
    }
    $arr = array_unique($arr);
    return $arr;
}

/*
 * 基础sql
 */
function getHomeAreaSql($cat_id = '', $tags_id, $is_new,$hh_newbie,$home_area)
{
    $time = time();
    $sql = "SELECT
	g.`goods_id`,
	`goods_name`,
	`goods_img`,
	`goods_number`,
	`sort_sales`,
	`virtual_sales`,
	`shop_price`,
	`suppliers_id`,
	`money_line`,
	`sort_order`,
	`shop_price`,
	`is_instalment`,
	t.`tags_id` ,
	is_on_sale
	FROM " .$GLOBALS['ecs']->table('goods').
	" AS g LEFT JOIN ".$GLOBALS['ecs']->table('goods_tags') ." AS t ON g.`goods_id` = t.`goods_id` 
	AND `tags_id` = {$tags_id}
    AND `deleted_at` = 0
    AND (`start_at` < {$time})
    AND (`close_at` = 0 OR `close_at` > {$time})
    WHERE
        (
            `is_delete` = 0
        AND `sale_type` = 0
        )";
    if (array_key_exists($tags_id,$home_area)) {
        $sql .= ' and is_on_sale = 1 and is_check = 2';
    }
    if(!empty($cat_id)) {
        $sql .= " AND (`cat_id` IN ({$cat_id}) 
        OR EXISTS (
		SELECT * FROM ".$GLOBALS['ecs']->table('goods_tags'). " as gt WHERE gt.`goods_id` = g.`goods_id`
		AND `tags_id` = {$tags_id}
		AND `deleted_at` = 0
		AND (`start_at` < {$time})
		AND (`close_at` = 0 OR `close_at` > {$time})))";
    }else{
        $sql .= " AND ( EXISTS (
		SELECT * FROM ".$GLOBALS['ecs']->table('goods_tags')." 
		AS gt WHERE
			gt.`goods_id` = g.`goods_id`
		AND `tags_id` = {$tags_id}
		AND `deleted_at` = 0
		AND (`start_at` < {$time})
		AND (`close_at` = 0 OR `close_at` > {$time})))";
    }
    if($is_new){
        $sql.= " AND `hh_newbie` = {$hh_newbie}";
    }
    return $sql;
}

/*
 * 首页专区排序sql
 */
function getHomeAreaOrderBy($tags_id, $home_area = '')
{
    $sql = " AND `cat_id` <> 3 ORDER BY
	goods_number = 0,IF (`tags_id` = $tags_id, 0, 1), CASE WHEN `tags_id` = $tags_id THEN sort_order END,
	goods_id desc, order_no2 = 1,order_no6 * 0.6 + order_no2 * 0.4";
    if (array_key_exists($tags_id,$home_area)) {
        $sql .= ' limit 12';
    }
    return $sql;
}


/*
 * 百元特价
 */
function getHundredAreaOrderBy()
{
    $sql = " AND `cat_id` <> 3 ORDER BY goods_number = 0,virtual_sales + sort_sales DESC";
    return $sql;
}

/*
 * 高额浣币
 */
function getHighHbOrderBy()
{
    $sql = " AND `cat_id` <> 3 ORDER BY goods_number = 0,`order_no2` ASC";
    return $sql;
}

function getOtherOrderBy()
{
    $sql = " AND `cat_id` <> 3 ORDER BY goods_number = 0, sort_order,order_no2 = 1,order_no6 * 0.6 + order_no2 * 0.4 LIMIT 12";
    return $sql;
}

//24hTOP榜
function getTopSql()
{
    $sql = "SELECT
	`goods_id`,
	`goods_name`,
	`goods_img`,
	`goods_number`,
	`sort_sales`,
	`virtual_sales`,
	`shop_price`,
	`suppliers_id`,
	`money_line`,
	`sort_order`
FROM
	".$GLOBALS['ecs']->table('goods')."
WHERE
	`is_alone_sale` = 1
AND `is_hot` = 1
AND (
	`is_delete` = 0
	AND `sale_type` = 0
	AND `is_on_sale` = 1
	AND `is_check` = 2
	AND `hh_newbie` = 0
)
AND `cat_id` <> 3
ORDER BY
	goods_number = 0,
	`sort_order` ASC,
	`sale_time` DESC";
    return $sql;
}

/*
 * 获取当前tags下的商品
 */
function getTagsGoods($tags_id = 0, $hot = 15) {
    if (empty($tags_id)){
        sys_msg('异常');
    }
    $now = gmtime();
    if ($tags_id != $hot) {
        $sql = "SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods_tags') . " WHERE tags_id = {$tags_id} AND deleted_at = 0
		AND (`start_at` < {$now})
		AND (`close_at` = 0 OR `close_at` > {$now})";
    } else {
        $sql = getTopSql();
    }
    $data = $GLOBALS['db']->getAll($sql);
    $ids  = array_column($data,'goods_id');
    return $ids;
}


?>
