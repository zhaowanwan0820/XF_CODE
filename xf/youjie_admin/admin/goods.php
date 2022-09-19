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
include_once(ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
$exc = new exchange($ecs->table('goods'), $db, 'goods_id', 'goods_name');

/*------------------------------------------------------ */
//-- 商品列表，商品回收站
/*------------------------------------------------------ */


// 商家合作类型
$suppliers_cooperate_type   = 0;
if (!empty($_SESSION['suppliers_info']['cooperate_type'])) {
	$suppliers_cooperate_type   = $_SESSION['suppliers_info']['cooperate_type'];
}
$smarty->assign('suppliers_cooperate_type', $suppliers_cooperate_type);


//运费地区列表ajax
if ($_REQUEST['act'] == 'ajax_shipping_area') {

    //运费地区列表
    $sql    = "select * FROM " . $ecs->table('shipping_tpl_area') .
            " WHERE tpl_id = " . $_REQUEST['tpl_id'] ;
    $shipping_tpl_area_list = $db->getAll($sql);

    $sql    = "select * FROM " . $ecs->table('shipping_tpl') .
                    " WHERE id = " . $_REQUEST["tpl_id"] ;
    $shipping_tpl_msg  = $db->getRow($sql);


    $arr    = array();
    $arr['shipping_tpl_msg']        = $shipping_tpl_msg;
    $arr['shipping_tpl_area_list']  = $shipping_tpl_area_list;
    echo json_encode($arr);
    exit();
}

if ($_REQUEST['act'] == 'list' || $_REQUEST['act'] == 'trash')
{
    $admin_suppliers_id = $_SESSION['suppliers_id'];
    admin_priv('goods_lists');

    $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
    $code   = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
    $suppliers_id = isset($_REQUEST['suppliers_id']) ? (empty($_REQUEST['suppliers_id']) ? '' : trim($_REQUEST['suppliers_id'])) : '';
    $is_on_sale = isset($_REQUEST['is_on_sale']) ? ((empty($_REQUEST['is_on_sale']) && $_REQUEST['is_on_sale'] === 0) ? '' : trim($_REQUEST['is_on_sale'])) : '';

    $handler_list = array();
    $handler_list['virtual_card'][] = array('url'=>'virtual_card.php?act=card', 'title'=>$_LANG['card'], 'img'=>'icon_send_bonus.gif');
    $handler_list['virtual_card'][] = array('url'=>'virtual_card.php?act=replenish', 'title'=>$_LANG['replenish'], 'img'=>'icon_add.svg');
    $handler_list['virtual_card'][] = array('url'=>'virtual_card.php?act=batch_card_add', 'title'=>$_LANG['batch_card_add'], 'img'=>'icon_output.gif');

    if ($_REQUEST['act'] == 'list' && isset($handler_list[$code]))
    {
        $smarty->assign('add_handler',      $handler_list[$code]);
    }

    /* 供货商名 */
    $suppliers_list_name = suppliers_list_name($admin_suppliers_id ? "suppliers_id in ({$admin_suppliers_id})" : '');
    $suppliers_exists = 1;
    if (empty($suppliers_list_name))
    {
        $suppliers_exists = 0;
    }
    $smarty->assign('admin_type', $_SESSION['admin_type']);
    $smarty->assign('is_on_sale', $is_on_sale);
    $smarty->assign('suppliers_id', $suppliers_id);
    $smarty->assign('suppliers_exists', $suppliers_exists);
    $smarty->assign('suppliers_list_name', $suppliers_list_name);
    unset($suppliers_list_name, $suppliers_exists);

    /* 模板赋值 */
    $goods_ur = array('' => $_LANG['01_goods_list'], 'virtual_card'=>$_LANG['50_virtual_card_list']);
    $ur_here = ($_REQUEST['act'] == 'list') ? $goods_ur[$code] : $_LANG['11_goods_trash'];
    $smarty->assign('ur_here', $ur_here);

    $action_link = ($_REQUEST['act'] == 'list') ? add_link($code) : array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
    $smarty->assign('action_link',  $action_link);
    $smarty->assign('code',     $code);
    $smarty->assign('cat_list',     cat_list(0, $cat_id));
    $smarty->assign('brand_list',   get_brand_list());
    $smarty->assign('intro_list',   get_intro_list());
    $smarty->assign('lang',         $_LANG);
    $smarty->assign('list_type',    $_REQUEST['act'] == 'list' ? 'goods' : 'trash');
    $smarty->assign('use_storage',  empty($_CFG['use_storage']) ? 0 : 1);

    $suppliers_list = suppliers_list_info(' is_check = 1 ' . ($admin_suppliers_id ? " and suppliers_id in ({$admin_suppliers_id})" : ''));
    $suppliers_list_count = count($suppliers_list);
    $smarty->assign('suppliers_list', ($suppliers_list_count == 0 ? 0 : $suppliers_list)); // 取供货商列表

    $where = '';
    if(!empty($admin_suppliers_id)){
        $where .= " and suppliers_id in ({$admin_suppliers_id})";
    }
    $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 4;//默认上架中
    if(isset($status) && $_REQUEST['act'] != 'trash'){
        switch ($status){
            case 0 :
                $where .= " and is_check = 0";
                $_REQUEST['sort_by'] = 'add_time';
                break;
            case 1 :
                $where .= " and is_check = 1";
                $_REQUEST['sort_by'] = 'check_time';
                break;
            case 2 :
                $where .= " and is_check = 2";
                $_REQUEST['sort_by'] = 'check_time';
                break;
            case 3 :
                $where .= " and is_check = 3";
                $_REQUEST['sort_by'] = 'check_time';
                break;
            case 4 :
                $where .= " and g.is_on_sale = 1 and is_check = 2";
                $_REQUEST['sort_by'] = 'sale_time';
                break;
            case 5 :
                $where .= " and g.is_on_sale = 0 and is_check = 2";
                $_REQUEST['sort_by'] = 'sale_time';
                break;
            case 6 :
                $where .= " and goods_number = 0 and is_check = 2";
                $_REQUEST['sort_by'] = 'sale_time';
                break;
        }
    }
    $goods_list = goods_list($_REQUEST['act'] == 'list' ? 0 : 1, ($_REQUEST['act'] == 'list') ? (($code == '') ? 1 : 0) : -1, $where);
    $smarty->assign('status', $goods_list['filter']['status']);
    $smarty->assign('goods_list',   $goods_list['goods']);
    $smarty->assign('filter',       $goods_list['filter']);
    $smarty->assign('record_count', $goods_list['record_count']);
    $smarty->assign('page_count',   $goods_list['page_count']);
    $smarty->assign('full_page',    1);

    /* 排序标记 */
    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 获取商品类型存在规格的类型 */
    $specifications = get_goods_type_specifications();
    $smarty->assign('specifications', $specifications);

    /* 显示商品列表页面 */
    assign_query_info();
    $htm_file = ($_REQUEST['act'] == 'list') ?
        'goods_list.htm' : (($_REQUEST['act'] == 'trash') ? 'goods_trash.htm' : 'group_list.htm');
    $smarty->assign('pageHtml',$htm_file);

    //数据统计
    $smarty->assign('goods_status', goods_status_count($_REQUEST['act'] == 'list' ? 0 : 1, ($_REQUEST['act'] == 'list') ? (($code == '') ? 1 : 0) : -1));

    $smarty->display($htm_file);
}

/*------------------------------------------------------ */
//-- 提交审核
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'submit_audit' )
{
    $goods_id = $_REQUEST['goods_id'] ? intval($_REQUEST['goods_id']) : 0;
    $user_id = $_SESSION['admin_id'];
    $time = gmtime();
    if ($exc->edit("is_check = '1',check_time = " .gmtime().", last_update=" . gmtime(), $goods_id)) {
        clear_cache_files();
        $goods_name = $exc->get_name($goods_id);
        goods_action_log($goods_id,1,'提交审核');
        admin_log(addslashes($goods_name), 'in_audit', 'goods');
        $url = 'goods.php?act=list&uselastfilter=1';
        ecs_header("Location: $url\n");
    }
}

/*------------------------------------------------------ */
//-- 审核商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'execute_audit' )
{
    if ($_SESSION['admin_type'] == 0) {
        $goods_id = $_REQUEST['goods_id'] ? intval($_REQUEST['goods_id']) : 0;
        $user_id = $_SESSION['admin_id'];
        $time = gmtime();
        if (isset($_REQUEST['audit_type'])) {
            if ($exc->edit("is_check = '2',check_time = " . gmtime() . ", last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                $goods_name = $exc->get_name($goods_id);
                goods_action_log($goods_id, 2, '审核通过');
                admin_log(addslashes($goods_name), 'audit_pass', 'goods');
            }
        } else {
            $remark = $_REQUEST['remark'] ? htmlspecialchars_decode(trim($_REQUEST['remark'])) : '';
            if ($exc->edit("is_check = '3',check_time = " . gmtime() . ", last_update=" . gmtime(), $goods_id)) {
                clear_cache_files();
                $goods_name = $exc->get_name($goods_id);
                $remark = '审核不通过：' . $remark;
                goods_action_log($goods_id, 3, $remark);
                admin_log(addslashes($goods_name), 'audit_no_pass', 'goods');
            }
        }
        $result = base64_decode($_SESSION['ECSCP']['lastfiltersql']);
//        $order_by_num = strpos($result, 'ORDER BY');
//        $sql1 = sub_str($result, $order_by_num, false);
//        $sql2 = substr($result, $order_by_num);
//        $limit_num = strpos($sql2, 'LIMIT');
//        $sql2 = sub_str($sql2, $limit_num, false);
//        $sql = $sql1 . $sql2 . ' limit 1';
        $sql = mb_substr($result, 0, mb_strpos($result, 'LIMIT')) . "LIMIT 1";
        //var_export($sql);exit;
        $arr = $db->getAll($sql);
        if (!empty($arr)) {
            $goods_id = $arr[0]['goods_id'];
            $url = 'goods.php?act=audit&goods_id=' . $goods_id;
            ecs_header("Location: $url\n");
        } else {
            $url = 'goods.php?act=list&status=2';
            ecs_header("Location: $url\n");
        }

    } else {
        sys_msg('无权审核', 0);
    }
}


/*------------------------------------------------------ */
//-- 添加新商品 编辑商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit' || $_REQUEST['act'] == 'copy' || $_REQUEST['act'] == 'view' || $_REQUEST['act'] == 'audit')
{
    $admin_suppliers_id = $_SESSION['suppliers_id'];
    include_once(ROOT_PATH . 'includes/fckeditor/fckeditor.php'); // 包含 html editor 类文件

    if (!empty($admin_suppliers_id)) {
        //运费模板列表
        $sql    = "select * FROM " . $ecs->table('shipping_tpl') .
                " WHERE suppliers_id in ($admin_suppliers_id) ";
        $shipping_tpl_list  = $db->getAll($sql);
        $smarty->assign('shipping_tpl_list', $shipping_tpl_list);
    }


    $is_add = $_REQUEST['act'] == 'add'; // 添加还是编辑的标识
    $is_copy = $_REQUEST['act'] == 'copy'; //是否复制
    $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
    $code=$code=='virual_card' ? 'virual_card': '';
    if ($code == 'virual_card')
    {
        admin_priv('virualcard'); // 检查权限
    }
    else
    {
        $_REQUEST['act'] == 'view' ?  admin_priv('goods_lists') : admin_priv('goods_manage'); // 检查权限
    }

    /* 供货商名 */
    $suppliers_list_name = suppliers_list_name($admin_suppliers_id ? "suppliers_id in ({$admin_suppliers_id})" : '');
    $suppliers_exists = 1;
    if (empty($suppliers_list_name))
    {
        $suppliers_exists = 0;
    }
    $smarty->assign('admin_type', $_SESSION['admin_type']);
    $smarty->assign('suppliers_exists', $suppliers_exists);
    $smarty->assign('suppliers_list_name', $suppliers_list_name);
    unset($suppliers_list_name, $suppliers_exists);

    /* 如果是安全模式，检查目录是否存在 */
    if (ini_get('safe_mode') == 1 && (!file_exists('../' . IMAGE_DIR . '/'.date('Ym')) || !is_dir('../' . IMAGE_DIR . '/'.date('Ym'))))
    {
        if (@!mkdir('../' . IMAGE_DIR . '/'.date('Ym'), 0777))
        {
            $warning = sprintf($_LANG['safe_mode_warning'], '../' . IMAGE_DIR . '/'.date('Ym'));
            $smarty->assign('warning', $warning);
        }
    }

    /* 如果目录存在但不可写，提示用户 */
    elseif (file_exists('../' . IMAGE_DIR . '/'.date('Ym')) && file_mode_info('../' . IMAGE_DIR . '/'.date('Ym')) < 2)
    {
        $warning = sprintf($_LANG['not_writable_warning'], '../' . IMAGE_DIR . '/'.date('Ym'));
        $smarty->assign('warning', $warning);
    }

    /* 取得商品信息 */
    if ($is_add)
    {
        /* 默认值 */
        $last_choose = array(0, 0);
        if (!empty($_COOKIE['ECSCP']['last_choose']))
        {
            //$last_choose = explode('|', $_COOKIE['ECSCP']['last_choose']);
        }
        $goods = array(
            'goods_id'      => 0,
            'goods_desc'    => '',
            'cat_id'        => $last_choose[0],
            'brand_id'      => $last_choose[1],
            'is_on_sale'    => '0',
            'is_sell_out'   => '0',
            'is_alone_sale' => '1',
            'is_instalment' => 0,
            'hh_newbie'    => '0',
            'is_shipping'   => '0',
            'delivery_time'   => '0',
            'refund_guarantee'   => '1',
            'real_guarantee'   => '1',
            'other_cat'     => array(), // 扩展分类
            'goods_type'    => 0,       // 商品类型
            'shop_price'    => 0,
            'promote_price' => 0,
            'market_price'  => 0,
            'virtual_sales'  => 0,
            'integral'      => 0,
        	'money_line' => 0, //权益币支付额度
            'goods_number'  => $_CFG['default_storage'],
            'warn_number'   => 1,
            'promote_start_date' => local_date('Y-m-d'),
            'promote_end_date'   => local_date('Y-m-d', local_strtotime('+1 month')),
            'goods_weight'  => 0,
            'give_integral' => -1,
            'rank_integral' => -1,
            'token_type'    => 0,
        );

        if ($code != '')
        {
            $goods['goods_number'] = 0;
        }

        /* 关联商品 */
        $link_goods_list = array();
        $sql = "DELETE FROM " . $ecs->table('link_goods') .
                " WHERE (goods_id = 0 OR link_goods_id = 0)" .
                " AND admin_id = '$_SESSION[admin_id]'";
        $db->query($sql);

        /* 组合商品 */
        $group_goods_list = array();
        $sql = "DELETE FROM " . $ecs->table('group_goods') .
                " WHERE parent_id = 0 AND admin_id = '$_SESSION[admin_id]'";
        $db->query($sql);

        /* 关联文章 */
        $goods_article_list = array();
        $sql = "DELETE FROM " . $ecs->table('goods_article') .
                " WHERE goods_id = 0 AND admin_id = '$_SESSION[admin_id]'";
        $db->query($sql);

        /* 属性 */
        $sql = "DELETE FROM " . $ecs->table('goods_attr') . " WHERE goods_id = 0";
        $db->query($sql);

        /* 图片列表 */
        $img_list = array();
    }
    else
    {
        /* 商品信息 */
        $sql = "SELECT * FROM " . $ecs->table('goods') . "  WHERE goods_id = '$_REQUEST[goods_id]'";
        $goods = $db->getRow($sql);

         if (!empty($goods["suppliers_id"])) {

            // 商家信息
            $sql_suppliers_msg  = "SELECT * FROM " . $ecs->table('suppliers') . "  WHERE suppliers_id = '$goods[suppliers_id]'";
            $suppliers_msg      = $db->getRow($sql_suppliers_msg);
            $smarty->assign('suppliers_msg', $suppliers_msg);

            // 运费模板列表
            $sql    = "select * FROM " . $ecs->table('shipping_tpl') .
                    " WHERE suppliers_id = " . $goods["suppliers_id"] ;
            $shipping_tpl_list  = $db->getAll($sql);
            $smarty->assign('shipping_tpl_list', $shipping_tpl_list);

        }


        //运费地区列表
        if (!empty($goods["shipping_id"])) {

            $sql    = "select * FROM " . $ecs->table('shipping_tpl') .
                    " WHERE id = " . $goods["shipping_id"] ;
            $shipping_tpl_msg  = $db->getRow($sql);
            $smarty->assign('shipping_tpl_msg', $shipping_tpl_msg);

            $sql    = "select * FROM " . $ecs->table('shipping_tpl_area') .
                    " WHERE tpl_id = " . $goods["shipping_id"] ;
            $shipping_tpl_area_list  = $db->getAll($sql);
            $smarty->assign('shipping_tpl_area_list', $shipping_tpl_area_list);

        }

        /* 虚拟卡商品复制时, 将其库存置为0*/
        if ($is_copy && $code != '')
        {
            $goods['goods_number'] = 0;
        }

        $goods['custom_specification'] = getCustomSpecification($_REQUEST['goods_id']);
        if (empty($goods) === true)
        {
            /* 默认值 */
            $goods = array(
                'goods_id'      => 0,
                'goods_desc'    => '',
                'cat_id'        => 0,
                'is_on_sale'    => '1',
                'is_sell_out'   => '0',
                'is_alone_sale' => '1',
                'is_instalment' => 0,
                'hh_newbie'    => '0',
                'is_shipping'   => '0',
                'delivery_time'   => '0',
                'refund_guarantee'   => '1',
                'real_guarantee'   => '1',
                'other_cat'     => array(), // 扩展分类
                'goods_type'    => 0,       // 商品类型
                'shop_price'    => 0,
                'promote_price' => 0,
                'market_price'  => 0,
                'virtual_sales'  => 0,
                'integral'      => 0,
            	'money_line' => -1, //权益币支付额度
                'goods_number'  => 1,
                'warn_number'   => 1,
                'promote_start_date' => local_date('Y-m-d'),
                'promote_end_date'   => local_date('Y-m-d', gmstr2tome('+1 month')),
                'goods_weight'  => 0,
                'give_integral' => -1,
                'rank_integral' => -1
            );
        }
        /* 获取商品类型存在规格的类型 */
        $specifications = get_goods_type_specifications();
        $goods['specifications_id'] = $specifications[$goods['goods_type']];
        $_attribute = get_goods_specifications_list($goods['goods_id']);
        $goods['_attribute'] = empty($_attribute) ? '' : 1;

        /* 根据商品重量的单位重新计算 */
        if ($goods['goods_weight'] > 0)
        {
            $goods['goods_weight_by_unit'] = ($goods['goods_weight'] >= 1) ? $goods['goods_weight'] : ($goods['goods_weight'] / 0.001);
        }

        if (!empty($goods['goods_brief']))
        {
            //$goods['goods_brief'] = trim_right($goods['goods_brief']);
            $goods['goods_brief'] = $goods['goods_brief'];
        }
        if (!empty($goods['keywords']))
        {
            //$goods['keywords']    = trim_right($goods['keywords']);
            $goods['keywords']    = $goods['keywords'];
        }

        /* 如果不是促销，处理促销日期 */
        if (isset($goods['is_promote']) && $goods['is_promote'] == '0')
        {
            unset($goods['promote_start_date']);
            unset($goods['promote_end_date']);
        }
        else
        {
            $goods['promote_start_date'] = local_date('Y-m-d', $goods['promote_start_date']);
            $goods['promote_end_date'] = local_date('Y-m-d', $goods['promote_end_date']);
        }

        /* 如果是复制商品，处理 */
        if ($_REQUEST['act'] == 'copy')
        {
            // 商品信息
            $goods['goods_id'] = 0;
            $goods['goods_sn'] = '';
            $goods['goods_name'] = '';
            $goods['goods_img'] = '';
            $goods['goods_thumb'] = '';
            $goods['original_img'] = '';

            // 扩展分类不变

            // 关联商品
            $sql = "DELETE FROM " . $ecs->table('link_goods') .
                    " WHERE (goods_id = 0 OR link_goods_id = 0)" .
                    " AND admin_id = '$_SESSION[admin_id]'";
            $db->query($sql);

            $sql = "SELECT '0' AS goods_id, link_goods_id, is_double, '$_SESSION[admin_id]' AS admin_id" .
                    " FROM " . $ecs->table('link_goods') .
                    " WHERE goods_id = '$_REQUEST[goods_id]' ";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $db->autoExecute($ecs->table('link_goods'), $row, 'INSERT');
            }

            $sql = "SELECT goods_id, '0' AS link_goods_id, is_double, '$_SESSION[admin_id]' AS admin_id" .
                    " FROM " . $ecs->table('link_goods') .
                    " WHERE link_goods_id = '$_REQUEST[goods_id]' ";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $db->autoExecute($ecs->table('link_goods'), $row, 'INSERT');
            }

            // 配件
            $sql = "DELETE FROM " . $ecs->table('group_goods') .
                    " WHERE parent_id = 0 AND admin_id = '$_SESSION[admin_id]'";
            $db->query($sql);

            $sql = "SELECT 0 AS parent_id, goods_id, goods_price, '$_SESSION[admin_id]' AS admin_id " .
                    "FROM " . $ecs->table('group_goods') .
                    " WHERE parent_id = '$_REQUEST[goods_id]' ";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $db->autoExecute($ecs->table('group_goods'), $row, 'INSERT');
            }

            // 关联文章
            $sql = "DELETE FROM " . $ecs->table('goods_article') .
                    " WHERE goods_id = 0 AND admin_id = '$_SESSION[admin_id]'";
            $db->query($sql);

            $sql = "SELECT 0 AS goods_id, article_id, '$_SESSION[admin_id]' AS admin_id " .
                    "FROM " . $ecs->table('goods_article') .
                    " WHERE goods_id = '$_REQUEST[goods_id]' ";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $db->autoExecute($ecs->table('goods_article'), $row, 'INSERT');
            }

            // 图片不变

            // 商品属性
            $sql = "DELETE FROM " . $ecs->table('goods_attr') . " WHERE goods_id = 0";
            $db->query($sql);

            $sql = "SELECT 0 AS goods_id, attr_id, attr_value, attr_price " .
                    "FROM " . $ecs->table('goods_attr') .
                    " WHERE goods_id = '$_REQUEST[goods_id]' ";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $db->autoExecute($ecs->table('goods_attr'), addslashes_deep($row), 'INSERT');
            }
        }

        // 扩展分类
        $other_cat_list = array();
        $sql = "SELECT cat_id FROM " . $ecs->table('goods_cat') . " WHERE goods_id = '$_REQUEST[goods_id]'";
        $goods['other_cat'] = $db->getCol($sql);
        foreach ($goods['other_cat'] AS $cat_id)
        {
            $other_cat_list[$cat_id] = cat_list(0, $cat_id);
        }
        $smarty->assign('other_cat_list', $other_cat_list);

        $link_goods_list    = get_linked_goods($goods['goods_id']); // 关联商品
        $group_goods_list   = get_group_goods($goods['goods_id']); // 配件
        $goods_article_list = get_goods_articles($goods['goods_id']);   // 关联文章


        /* 商品图片路径 */
        if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 10) && !empty($goods['original_img']))
        {
            $goods['goods_img'] = get_image_path($_REQUEST['goods_id'], $goods['goods_img']);
            $goods['goods_thumb'] = get_image_path($_REQUEST['goods_id'], $goods['goods_thumb'], true);
        }

        /* 图片列表 */
        $sql = "SELECT * FROM " . $ecs->table('goods_gallery') . " WHERE goods_id = '$goods[goods_id]'";
        $img_list = $db->getAll($sql);

        /* 格式化相册图片路径 */
        if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0))
        {
            foreach ($img_list as $key => $gallery_img)
            {
                $gallery_img[$key]['img_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], false, 'gallery');
                $gallery_img[$key]['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], true, 'gallery');
            }
        }
        else
        {
            foreach ($img_list as $key => $gallery_img)
            {
                $gallery_img[$key]['thumb_url'] = '../' . (empty($gallery_img['thumb_url']) ? $gallery_img['img_url'] : $gallery_img['thumb_url']);
            }
        }
    }

    /* 拆分商品名称样式 */
    $goods_name_style = explode('+', empty($goods['goods_name_style']) ? '+' : $goods['goods_name_style']);

    /* 创建 html editor */
    create_html_editor('goods_desc', $goods['goods_desc']);

    /*获取省市 2019.2.19*/
    $region = array();
    $sql = "SELECT * FROM " . $ecs->table('region') . " WHERE region_type = '1'";
    $region = $db->getAll($sql);
    foreach ($region as $key=>$val)
    {
        $region_id = $val['region_id'];
        $sql = "SELECT * FROM " . $ecs->table('region') . " WHERE parent_id = '$region_id' and region_type = '2'";
        $city = $db->getAll($sql);
        $region[$key]['childrens'] = $city;
    }
    $smarty->assign('region', json_encode($region));
    /*获取该商品非配送城市 2019.02.21*/
    $no_cityname = array();
    if(!empty($goods['delivery_area']))
    {
        $no_city = explode(',',$goods['delivery_area']);
        foreach ($no_city as $v)
        {
            $sql = "SELECT * FROM " . $ecs->table('region') . " WHERE region_id = '$v'";
            $no_cityname[] = $db->getRow($sql);
        }
    }
    $smarty->assign('no_cityname',    json_encode($no_cityname));
    if($is_add){
        $form_act = 'insert';
        $uc_here = (empty($code) ? $_LANG['02_goods_add'] : $_LANG['51_virtual_card_add']);
    }elseif ($_REQUEST['act'] == 'edit'){
        $form_act = 'update';
        $uc_here = $_LANG['edit_goods'];
    }elseif ($_REQUEST['act'] == 'audit'){
        $form_act = 'execute_audit';
        $uc_here = $_LANG['audit_goods'];
    }elseif($_REQUEST['act'] == 'view'){
        $uc_here = $_LANG['view_goods'];
    }else{
        $form_act = 'insert';
        $uc_here = $_LANG['copy_goods'];
    }
    if($_SESSION['admin_type'] == 1){
        $sql = "SELECT type FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = $admin_suppliers_id limit 1";
        $type = $db->getOne($sql);
        $smarty->assign('set_borrow',$type == 1 ? true : false);
    }else{
        $smarty->assign('set_borrow',true);
    }

    // 商品分期信息
    if($goods['is_instalment'] == 1){
        $sql = "SELECT `method`, `num`, `payment_plan` FROM " . $GLOBALS['ecs']->table('goods_instalment') . " WHERE `goods_id`=$goods[goods_id]";
        $instalmentModel = $db->getAll($sql);
        $smarty->assign('instalments', $instalmentModel);
    }

    /* 模板赋值 */
    $smarty->assign('code',    $code);
    $smarty->assign('shipping_id',  $goods['shipping_id']);
    $smarty->assign('ur_here', $uc_here);
    $smarty->assign('action_link', list_link($is_add, $code));
    $smarty->assign('goods', $goods);
    $smarty->assign('goods_name_color', $goods_name_style[0]);
    $smarty->assign('goods_name_style', $goods_name_style[1]);
    $smarty->assign('cat_list', cat_list(0, $goods['cat_id']));
    $category_ids = get_category_ids($goods['cat_id']);
    $smarty->assign('category_ids', $category_ids);
    $smarty->assign('category_list', category_list());
    $smarty->assign('category_list_2', $category_ids && $category_ids[2] ? category_list($category_ids[2]) : []);
    $smarty->assign('category_list_3', $category_ids && $category_ids[1] ? category_list($category_ids[1]) : []);
    $smarty->assign('brand_list', get_brand_list());
    $smarty->assign('unit_list', get_unit_list());
    $smarty->assign('user_rank_list', get_user_rank_list());
    $smarty->assign('weight_unit', $is_add ? '1' : ($goods['goods_weight'] >= 1 ? '1' : '0.001'));
    $smarty->assign('cfg', $_CFG);

    $smarty->assign('form_act',$form_act );
    if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
    {
        $smarty->assign('is_add', true);
    }
    if(!$is_add)
    {
        $smarty->assign('member_price_list', get_member_price_list($_REQUEST['goods_id']));
    }
    $smarty->assign('link_goods_list', $link_goods_list);
    $smarty->assign('group_goods_list', $group_goods_list);
    $smarty->assign('goods_article_list', $goods_article_list);
    $smarty->assign('img_list', $img_list);
    $smarty->assign('goods_type_list', goods_type_list($goods['goods_type']));
    $smarty->assign('gd', gd_version());
    $smarty->assign('thumb_width', $_CFG['thumb_width']);
    $smarty->assign('thumb_height', $_CFG['thumb_height']);
  /*if($goods['add_time'] && $goods['add_time'] < strtotime('2019-04-18')){
        $smarty->assign('goods_attr_html', build_attr_html($goods['goods_type'], $goods['goods_id']));
    }else{
        $smarty->assign('goods_attr_html', build_attr_html($goods['goods_type'], $goods['goods_id'], $goods['cat_id']));
    }*/
    $smarty->assign('goods_attr_html', build_attr_html($goods['goods_type'], $goods['goods_id'], $goods['cat_id']));
    $volume_price_list = '';
    if(isset($_REQUEST['goods_id']))
    {
    $volume_price_list = get_volume_price_list($_REQUEST['goods_id']);
    }
    if (empty($volume_price_list))
    {
        $volume_price_list = array('0'=>array('number'=>'','price'=>''));
    }
    if($_REQUEST['act'] == 'view' || $_REQUEST['act'] == 'audit'){
        $smarty->assign('view', true);
    }
    if($_REQUEST['act'] == 'audit'){
        $smarty->assign('is_audit', true);
    }
    if($_REQUEST['act'] == 'edit'){
        $smarty->assign('is_edit', true);
    }
    if($_REQUEST['act'] == 'add'){
        $smarty->assign('is_real_add', true);
    }
    if ($_REQUEST['act'] == 'view' || $_REQUEST['act'] == 'edit' || $_REQUEST['act'] == 'audit')
    {
        $sql = "SELECT ga.*,u.user_name FROM " . $ecs->table('goods_action')  .
            "as ga left join ". $ecs->table('goods') ." as g on ga.goods_id = g.goods_id" .
            " left join ". $ecs->table('admin_user') . " as u on ga.user_id = u.user_id".
            " where g.is_delete = 0 AND ga.goods_id = '$_GET[goods_id]' order by ga.add_time desc";
        $goods_action = $db->getAll($sql);
        foreach ($goods_action as $k=>$v){
            $goods_action[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
        }
        $smarty->assign('goods_action', $goods_action);
        $smarty->assign('show_action', true);
    }
    $smarty->assign('volume_price_list', $volume_price_list);
    //商家信息
    if($_REQUEST['act'] == 'audit'){
        $smarty->assign('suppliers_info', suppliers_info($goods['suppliers_id']));
    }
    $smarty->assign('status', isset($_REQUEST['status']) ? $_REQUEST['status'] : '');
    /* 显示商品信息页面 */
    assign_query_info();
    /*if($goods['add_time'] && $goods['add_time'] < strtotime('2019-04-18')){
        $smarty->display('goods_info.htm');
    }else{
        $smarty->display('goods_info_new.htm');
    }*/
    $smarty->display('goods_info_new.htm');
}

/*------------------------------------------------------ */
//-- 插入商品 更新商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    addLog('action :'.$_REQUEST['act'].' data:'.print_r($_REQUEST,true),'info',$_SESSION['admin_name'],'goods');

    $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

    // if (empty($_POST[shipping_box])) {
    //     $_POST[shipping_id] = 0;
    // }

    /* 是否处理缩略图 */
    $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)? false : true;
    if ($code == 'virtual_card')
    {
        admin_priv('virualcard'); // 检查权限
    }
    else
    {
        admin_priv('goods_manage'); // 检查权限
    }

    /* 检查货号是否重复 */
    if ($_POST['goods_sn'])
    {
        $sql = "SELECT COUNT(*) FROM " . $ecs->table('goods') .
                " WHERE goods_sn = '$_POST[goods_sn]' AND is_delete = 0 AND goods_id <> '$_POST[goods_id]'";
        if ($db->getOne($sql) > 0)
        {
            sys_msg($_LANG['goods_sn_exists'], 1, array(), false);
        }
    }

    /* 检查图片：如果有错误，检查尺寸是否超过最大值；否则，检查文件类型 */
    if (isset($_FILES['goods_img']['error'])) // php 4.2 版本才支持 error
    {
        // 最大上传文件大小
        $php_maxsize = ini_get('upload_max_filesize');
        $htm_maxsize = '2M';

        // 商品图片
        if ($_FILES['goods_img']['error'] == 0)
        {
            if (!$image->check_img_type($_FILES['goods_img']['type']))
            {
                sys_msg($_LANG['invalid_goods_img'], 1, array(), false);
            }
        }
        elseif ($_FILES['goods_img']['error'] == 1)
        {
            sys_msg(sprintf($_LANG['goods_img_too_big'], $php_maxsize), 1, array(), false);
        }
        elseif ($_FILES['goods_img']['error'] == 2)
        {
            sys_msg(sprintf($_LANG['goods_img_too_big'], $htm_maxsize), 1, array(), false);
        }

        // 商品缩略图
        if (isset($_FILES['goods_thumb']))
        {
            if ($_FILES['goods_thumb']['error'] == 0)
            {
                if (!$image->check_img_type($_FILES['goods_thumb']['type']))
                {
                    sys_msg($_LANG['invalid_goods_thumb'], 1, array(), false);
                }
            }
            elseif ($_FILES['goods_thumb']['error'] == 1)
            {
                sys_msg(sprintf($_LANG['goods_thumb_too_big'], $php_maxsize), 1, array(), false);
            }
            elseif ($_FILES['goods_thumb']['error'] == 2)
            {
                sys_msg(sprintf($_LANG['goods_thumb_too_big'], $htm_maxsize), 1, array(), false);
            }
        }

        // 相册图片
        foreach ($_FILES['img_url']['error'] AS $key => $value)
        {
            if ($value == 0)
            {
                if (!$image->check_img_type($_FILES['img_url']['type'][$key]))
                {
                    sys_msg(sprintf($_LANG['invalid_img_url'], $key + 1), 1, array(), false);
                }
            }
            elseif ($value == 1)
            {
                sys_msg(sprintf($_LANG['img_url_too_big'], $key + 1, $php_maxsize), 1, array(), false);
            }
            elseif ($_FILES['img_url']['error'] == 2)
            {
                sys_msg(sprintf($_LANG['img_url_too_big'], $key + 1, $htm_maxsize), 1, array(), false);
            }
        }
    }
    /* 4.1版本 */
    else
    {
        // 商品图片
        if ($_FILES['goods_img']['tmp_name'] != 'none')
        {
            if (!$image->check_img_type($_FILES['goods_img']['type']))
            {

                sys_msg($_LANG['invalid_goods_img'], 1, array(), false);
            }
        }

        // 商品缩略图
        if (isset($_FILES['goods_thumb']))
        {
            if ($_FILES['goods_thumb']['tmp_name'] != 'none')
            {
                if (!$image->check_img_type($_FILES['goods_thumb']['type']))
                {
                    sys_msg($_LANG['invalid_goods_thumb'], 1, array(), false);
                }
            }
        }

        // 相册图片
        foreach ($_FILES['img_url']['tmp_name'] AS $key => $value)
        {
            if ($value != 'none')
            {
                if (!$image->check_img_type($_FILES['img_url']['type'][$key]))
                {
                    sys_msg(sprintf($_LANG['invalid_img_url'], $key + 1), 1, array(), false);
                }
            }
        }
    }

    /* 插入还是更新的标识 */
    $is_insert = $_REQUEST['act'] == 'insert';

    /* 处理商品图片 */
    $goods_img        = '';  // 初始化商品图片
    $goods_thumb      = '';  // 初始化商品缩略图
    $original_img     = '';  // 初始化原始图片
    $old_original_img = '';  // 初始化原始图片旧图

    // 如果上传了商品图片，相应处理
    if (($_FILES['goods_img']['tmp_name'] != '' && $_FILES['goods_img']['tmp_name'] != 'none'))
    {
        $is_url_goods_img = 0;
        if ($_REQUEST['goods_id'] > 0)
        {
            /* 删除原来的图片文件 */
            $sql = "SELECT goods_thumb, goods_img, original_img " .
                    " FROM " . $ecs->table('goods') .
                    " WHERE goods_id = '$_REQUEST[goods_id]'";
            $row = $db->getRow($sql);
            if ($row['goods_thumb'] != '' && is_file('../' . $row['goods_thumb']))
            {
                @unlink('../' . $row['goods_thumb']);
            }
            if ($row['goods_img'] != '' && is_file('../' . $row['goods_img']))
            {
                @unlink('../' . $row['goods_img']);
            }
            if ($row['original_img'] != '' && is_file('../' . $row['original_img']))
            {
                /* 先不处理，以防止程序中途出错停止 */
                //$old_original_img = $row['original_img']; //记录旧图路径
            }
            /* 清除原来商品图片 */
            if ($proc_thumb === false)
            {
                get_image_path($_REQUEST[goods_id], $row['goods_img'], false, 'goods', true);
                get_image_path($_REQUEST[goods_id], $row['goods_thumb'], true, 'goods', true);
            }
        }

        if (empty($is_url_goods_img))
        {
            $original_img   = $image->upload_image($_FILES['goods_img']); // 原始图片
        }
        elseif ($_POST['goods_img_url'])
        {

            if(preg_match('/(.jpg|.png|.gif|.jpeg)$/',$_POST['goods_img_url']) && copy(trim($_POST['goods_img_url']), ROOT_PATH . 'temp/' . basename($_POST['goods_img_url'])))
            {
                  $original_img = 'temp/' . basename($_POST['goods_img_url']);
            }

        }

        if ($original_img === false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }
        $goods_img      = $original_img;   // 商品图片

        /* 复制一份相册图片 */
        /* 添加判断是否自动生成相册图片 */
        if ($_CFG['auto_generate_gallery'])
        {
            $img        = $original_img;   // 相册图片
            $pos        = strpos(basename($img), '.');
            $newname    = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
            if (!copy('../' . $img, '../' . $newname))
            {
                sys_msg('fail to copy file: ' . realpath('../' . $img), 1, array(), false);
            }
            $img        = $newname;

            $gallery_img    = $img;
            $gallery_thumb  = $img;
        }

        // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
        if ($proc_thumb && $image->gd_version() > 0 && $image->check_img_function($_FILES['goods_img']['type']) || $is_url_goods_img)
        {

            if (empty($is_url_goods_img))
            {
                // 如果设置大小不为0，缩放图片
                if ($_CFG['image_width'] != 0 || $_CFG['image_height'] != 0)
                {
                    $goods_img = $image->make_thumb('../'. $goods_img , $GLOBALS['_CFG']['image_width'],  $GLOBALS['_CFG']['image_height']);
                    if ($goods_img === false)
                    {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                }

                /* 添加判断是否自动生成相册图片 */
                if ($_CFG['auto_generate_gallery'])
                {
                    $newname    = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                    if (!copy('../' . $img, '../' . $newname))
                    {
                        sys_msg('fail to copy file: ' . realpath('../' . $img), 1, array(), false);
                    }
                    $gallery_img        = $newname;
                }

                // 加水印
                if (intval($_CFG['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark']))
                {
                    if ($image->add_watermark('../'.$goods_img,'',$GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false)
                    {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                    /* 添加判断是否自动生成相册图片 */
                    if ($_CFG['auto_generate_gallery'])
                    {
                        if ($image->add_watermark('../'. $gallery_img,'',$GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false)
                        {
                            sys_msg($image->error_msg(), 1, array(), false);
                        }
                    }
                }
            }

            // 相册缩略图
            /* 添加判断是否自动生成相册图片 */
            if ($_CFG['auto_generate_gallery'])
            {
                if ($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0)
                {
                    $gallery_thumb = $image->make_thumb('../' . $img, $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
                    if ($gallery_thumb === false)
                    {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                }
            }
        }
        /* 取消该原图复制流程 */
        // else
        // {
        //     /* 复制一份原图 */
        //     $pos        = strpos(basename($img), '.');
        //     $gallery_img = dirname($img) . '/' . $image->random_filename() . // substr(basename($img), $pos);
        //     if (!copy('../' . $img, '../' . $gallery_img))
        //     {
        //         sys_msg('fail to copy file: ' . realpath('../' . $img), 1, array(), false);
        //     }
        //     $gallery_thumb = '';
        // }
    }


    // 是否上传商品缩略图
    if (isset($_FILES['goods_thumb']) && $_FILES['goods_thumb']['tmp_name'] != '' &&
        isset($_FILES['goods_thumb']['tmp_name']) &&$_FILES['goods_thumb']['tmp_name'] != 'none')
    {
        // 上传了，直接使用，原始大小
        $goods_thumb = $image->upload_image($_FILES['goods_thumb']);
        if ($goods_thumb === false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }
    }
    else
    {
        // 未上传，如果自动选择生成，且上传了商品图片，生成所略图
        if ($proc_thumb && !empty($original_img))
        {
            // 如果设置缩略图大小不为0，生成缩略图
            if ($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0)
            {
                $goods_thumb = $image->make_thumb('../' . $original_img, $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
                if ($goods_thumb === false)
                {
                    sys_msg($image->error_msg(), 1, array(), false);
                }
            }
            else
            {
                $goods_thumb = $original_img;
            }
        }
    }


    /* 删除下载的外链原图 */
    if (!empty($is_url_goods_img))
    {
        unlink(ROOT_PATH . $original_img);
        empty($newname) || unlink(ROOT_PATH . $newname);
        $url_goods_img = $goods_img = $original_img = htmlspecialchars(trim($_POST['goods_img_url']));
    }


    /* 如果没有输入商品货号则自动生成一个商品货号 */
    if (empty($_POST['goods_sn']))
    {
        $max_id     = $is_insert ? $db->getOne("SELECT MAX(goods_id) + 1 FROM ".$ecs->table('goods')) : $_REQUEST['goods_id'];
        $goods_sn   = trim(generate_goods_sn($max_id));
    }
    else
    {
        $goods_sn   = trim($_POST['goods_sn']);
    }



    /*获取供应商
     * */
    $suppliers_id = $_POST['suppliers_id'] ?: intval($_SESSION['suppliers_id']);
    $sql = "SELECT suppliers_name FROM " . $GLOBALS['ecs']->table('suppliers') . " WHERE suppliers_id = $suppliers_id";
    $suppliers_name = $db->getOne($sql);
    /* 处理商品数据 */
    $shop_price = !empty($_POST['shop_price']) ? $_POST['shop_price'] : 0;
    $shipping_id        = !empty($_POST['shipping_id']) ? $_POST['shipping_id'] : 0;
    $settlement_money   = !empty($_POST['settlement_money']) ? $_POST['settlement_money'] : 0;
    $market_price = !empty($_POST['market_price']) ? $_POST['market_price'] : 0;
    $virtual_sales = !empty($_POST['virtual_sales']) ? $_POST['virtual_sales'] : 0;
    $promote_price = !empty($_POST['promote_price']) ? floatval($_POST['promote_price'] ) : 0;
    $is_promote = empty($promote_price) ? 0 : 1;
    $promote_start_date = ($is_promote && !empty($_POST['promote_start_date'])) ? local_strtotime($_POST['promote_start_date']) : 0;
    $promote_end_date = ($is_promote && !empty($_POST['promote_end_date'])) ? local_strtotime($_POST['promote_end_date']) : 0;
    $goods_weight = !empty($_POST['goods_weight']) ? (float)$_POST['goods_weight'] * (float)$_POST['weight_unit'] : 0;
    $is_best = isset($_POST['is_best']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_on_sale = isset($_POST['is_on_sale']) ? 1 : 0;
    $is_sell_out = isset($_POST['is_sell_out']) ? 1 : 0;        // 自动下架 2019-02-12
    $is_alone_sale = isset($_POST['is_alone_sale']) ? 1 : 0;
    $hh_newbie = isset($_POST['hh_newbie']) ? 1 : 0;          // 仅供新手商品 2019-03-18
    $is_shipping = isset($_POST['is_shipping']) ? 1 : 0;
    $goods_number = isset($_POST['goods_number']) ? intval($_POST['goods_number']) : 0;
    $warn_number = isset($_POST['warn_number']) ? intval($_POST['warn_number']) : 0;
    $goods_type = isset($_POST['goods_type']) ? $_POST['goods_type'] : 0;
    $give_integral = isset($_POST['give_integral']) ? intval($_POST['give_integral']) : '-1';
    $rank_integral = isset($_POST['rank_integral']) ? intval($_POST['rank_integral']) : '-1';
    $money_line = isset($_POST['money_line']) ? (float)$_POST['money_line'] : '-1';
    $suppliers_id = isset($_POST['suppliers_id']) ? intval($_POST['suppliers_id']) : intval($_SESSION['suppliers_id']);


    // 商家承诺 2019-06-11
    if(!isset($_POST['delivery_time'])){
        $delivery_time = 0;
    } else if($_POST['delivery_time'] == 'custom') {
        $delivery_time = intval($_POST['delivery_time_custom']) * 24;
    } else {
        $delivery_time = intval($_POST['delivery_time']);
    }

    $refund_guarantee = isset($_POST['refund_guarantee']) ? intval($_POST['refund_guarantee']) : 1;
    $real_guarantee = isset($_POST['real_guarantee']) ? 1 : 0;

    $goods_name_style = $_POST['goods_name_color'] . '+' . $_POST['goods_name_style'];

    $catgory_id = empty($_POST['cat_id']) ? '' : intval($_POST['cat_id']);
    $brand_id = empty($_POST['brand_id']) ? '' : intval($_POST['brand_id']);
    $delivery = empty($_POST['unexpress_region']) ? '' : $_POST['unexpress_region']; //2019.02.20  商品非配送范围
    $goods_location = !empty($_POST['goods_location']) ? htmlspecialchars(trim($_POST['goods_location'])) : 0;


    $goods_thumb = (empty($goods_thumb) && !empty($_POST['goods_thumb_url']) && goods_parse_url($_POST['goods_thumb_url'])) ? htmlspecialchars(trim($_POST['goods_thumb_url'])) : $goods_thumb;
    $goods_thumb = empty($goods_thumb)? $goods_img : $goods_thumb;
    $only_purchase = empty($_POST['only_purchase']) ? 0 : intval($_POST['only_purchase']);
    $company_name = !empty($_POST['company_name']) ? htmlspecialchars(trim($_POST['company_name'])) : '';
    $token_type = in_array($_POST['token_type'],[0,1,2])?intval($_POST['token_type']):0;


    // 分期
    $is_instalment        = !empty($_POST['is_instalment']) ? $_POST['is_instalment'] : 0;

    //不再校验项目
//    if (!empty($borrow_ids)) {
//        $postData = ['company_name' => $company_name, 'borrow_ids' => $borrow_ids];
//        $data = curl_request('https://www.itouzi.com/openApi/Shop/checkBorrow', 'POST', $postData);
//        if(!$data){
//            sys_msg('请求异常，网络错误', 0);
//        }
//        if ($data['code'] != 0 && isset($data['info'])) {
//            sys_msg($data['info'], 0);
//        }
//    }
    /* 入库 */
    if ($is_insert)
    {
        if ($code == '')
        {
            $sql = "INSERT INTO " . $ecs->table('goods') . " (goods_name, goods_name_style, goods_sn,provider_name, " .
            "cat_id, brand_id, shipping_id, settlement_money, shop_price, market_price, virtual_sales, is_promote, promote_price, " .
                    "promote_start_date, promote_end_date, goods_img, goods_thumb, original_img, keywords, goods_brief, " .
                    "seller_note, goods_weight, goods_number, warn_number, integral, money_line, give_integral, is_best, is_new, is_hot, " .
                    "is_on_sale, is_sell_out, is_alone_sale, hh_newbie, is_shipping, delivery_time, refund_guarantee, real_guarantee, goods_desc, " .
                    "add_time, last_update, goods_type, rank_integral, suppliers_id, delivery_area, goods_location, only_purchase,company_name, " .
                    "is_instalment, token_type)" .
                "VALUES ('$_POST[goods_name]', '$goods_name_style', '$goods_sn','$suppliers_name', '$catgory_id', " .
                "'$brand_id', '$shipping_id', '$settlement_money', '$shop_price', '$market_price', '$virtual_sales', '$is_promote','$promote_price', ".
                    "'$promote_start_date', '$promote_end_date', '$goods_img', '$goods_thumb', '$original_img', ".
                    "'$_POST[keywords]', '$_POST[goods_brief]', '$_POST[seller_note]', '$goods_weight', '$goods_number',".
                    " '$warn_number', '$_POST[integral]', '$money_line', '$give_integral', '$is_best', '$is_new', '$is_hot', '$is_on_sale', '$is_sell_out',".
                    " '$is_alone_sale', '$hh_newbie', $is_shipping, $delivery_time, $refund_guarantee, $real_guarantee,".
                    " '$_POST[goods_desc]', '" . gmtime() . "', '". gmtime() ."', '$goods_type', '$rank_integral', '$suppliers_id',".
                    " '$delivery', '$goods_location', '$only_purchase', '$company_name', '$is_instalment', $token_type)";
        }
        else
        {
            $sql = "INSERT INTO " . $ecs->table('goods') . " (goods_name, goods_name_style, goods_sn,provider_name, " .
                    "cat_id, brand_id, shipping_id, settlement_money, shop_price, market_price, virtual_sales, is_promote, promote_price, " .
                    "promote_start_date, promote_end_date, goods_img, goods_thumb, original_img, keywords, goods_brief, " .
                    "seller_note, goods_weight, goods_number, warn_number, integral, money_line, give_integral, is_best, is_new, is_hot, is_real, " .
                    "is_on_sale, is_sell_out, is_alone_sale, hh_newbie, is_shipping, delivery_time, refund_guarantee, real_guarantee, goods_desc, " .
                    "add_time, last_update, goods_type, extension_code, rank_integral, goods_location, suppliers_id, only_purchase, company_name, " .
                    "is_instalment, token_type)" .
                "VALUES ('$_POST[goods_name]', '$goods_name_style', '$goods_sn','$suppliers_name', '$catgory_id', " .
                    "'$brand_id', '$shipping_id', '$settlement_money', '$shop_price', '$market_price', '$virtual_sales', '$is_promote','$promote_price', ".
                    "'$promote_start_date', '$promote_end_date', '$goods_img', '$goods_thumb', '$original_img', ".
                    "'$_POST[keywords]', '$_POST[goods_brief]', '$_POST[seller_note]', '$goods_weight', '$goods_number',".
                    " '$warn_number', '$_POST[integral]', '$money_line', '$give_integral', '$is_best', '$is_new', '$is_hot', 0, '$is_on_sale', '$is_sell_out',".
                    " '$is_alone_sale', '$hh_newbie', $is_shipping, $delivery_time, $refund_guarantee, $real_guarantee,".
                    " '$_POST[goods_desc]', '" . gmtime() . "', '". gmtime() ."', '$goods_type', '$code', '$rank_integral',".
                    " '$goods_location', '$suppliers_id', '$only_purchase', '$company_name', '$is_instalment', $token_type)";
        }
    }
    else
    {
        /* 如果有上传图片，删除原来的商品图 */
        $sql = "SELECT goods_thumb, goods_img, original_img, is_check, is_on_sale " .
                    " FROM " . $ecs->table('goods') .
                    " WHERE goods_id = '$_REQUEST[goods_id]'";
        $row = $db->getRow($sql);
        if ($proc_thumb && $goods_img && $row['goods_img'] && !goods_parse_url($row['goods_img']))
        {
            @unlink(ROOT_PATH . $row['goods_img']);
            @unlink(ROOT_PATH . $row['original_img']);
        }

        if ($proc_thumb && $goods_thumb && $row['goods_thumb'] && !goods_parse_url($row['goods_thumb']))
        {
            @unlink(ROOT_PATH . $row['goods_thumb']);
        }

        $sql = "UPDATE " . $ecs->table('goods') . " SET " .
                "goods_name = '$_POST[goods_name]', " .
                "goods_name_style = '$goods_name_style', " .
                "goods_sn = '$goods_sn', " .
                "provider_name = '$suppliers_name', " .
                "cat_id = '$catgory_id', " .
                "brand_id = '$brand_id', " .
                "shipping_id = '$shipping_id', " .
                "settlement_money = '$settlement_money', " .
                "shop_price = '$shop_price', " .
                "market_price = '$market_price', " .
                "virtual_sales = '$virtual_sales', " .
                "is_promote = '$is_promote', " .
                "promote_price = '$promote_price', " .
                "promote_start_date = '$promote_start_date', " .
                "suppliers_id = '$suppliers_id', " .
                "delivery_area = '$delivery', " .           //非配送范围 2019.02.21
                "goods_location = '$goods_location', " .
                "token_type = '$token_type', " .
                "promote_end_date = '$promote_end_date', ";
        /* 如果有上传图片，需要更新数据库 */
        if ($goods_img)
        {
            $sql .= "goods_img = '$goods_img', original_img = '$original_img', ";
        }
        if ($goods_thumb)
        {
            $sql .= "goods_thumb = '$goods_thumb', ";
        }
        if ($code != '')
        {
            $sql .= "is_real=0, extension_code='$code', ";
        }
        if($_SESSION['admin_type']){
            $sql .= "is_check = 0, ";
        }
        if (isset($only_purchase))
        {
            $sql .= "only_purchase = '$only_purchase', ";
        }

        if($row['is_on_sale'] == 0){
            $sql .=  "goods_number = '$goods_number', " ;
        }
        $sql .= "keywords = '$_POST[keywords]', " .
                "goods_brief = '$_POST[goods_brief]', " .
                "seller_note = '$_POST[seller_note]', " .
                "goods_weight = '$goods_weight'," .
//                "goods_number = '$goods_number', " .
                "warn_number = '$warn_number', " .
                "integral = '$_POST[integral]', " .
                "money_line = '$money_line', ".
                "give_integral = '$give_integral', " .
                "rank_integral = '$rank_integral', " .
                //"is_best = '$is_best', " .
                //"is_new = '$is_new', " .
                //"is_hot = '$is_hot', " .
                //"is_on_sale = '$is_on_sale', " .
                "is_sell_out = '$is_sell_out', " .
                "is_alone_sale = '$is_alone_sale', " .
                "hh_newbie = '$hh_newbie', " .
                "is_shipping = '$is_shipping', " .
                "delivery_time = '$delivery_time', " .
                "refund_guarantee = '$refund_guarantee', " .
                "real_guarantee = '$real_guarantee', " .
                "goods_desc = '$_POST[goods_desc]', " .
                "last_update = '". gmtime() ."', ".
                "goods_type = '$goods_type', " .
                "company_name = '$company_name', " .
                "is_instalment = '$is_instalment' " .
                "WHERE goods_id = '$_REQUEST[goods_id]' LIMIT 1";
    }
    $db->query($sql);

    /* 商品编号 */
    $goods_id = $is_insert ? $db->insert_id() : $_REQUEST['goods_id'];

    /* 记录日志 */
    if ($is_insert)
    {
        admin_log($_POST['goods_name'], 'add', 'goods');
        goods_action_log($goods_id, 0, '添加商品');
    }
    else
    {
        admin_log($_POST['goods_name'], 'edit', 'goods');
        goods_action_log($goods_id, 7, '编辑商品');
    }

    // 删除之前的商品分期记录
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_instalment') . " WHERE `goods_id`=$goods_id LIMIT 5";
    $db->query($sql);

    // 插入商品分期
    if($is_instalment == 1){
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_instalment') . " (`goods_id`, `method`, `num`, `payment_plan`, `addtime`) VALUES ";
        foreach($_POST['instalments'] as $item){
            $sql .= "($goods_id, $item[method],  $item[num], '$item[payment_plan]', ". time() ."),";
        }
        $sql = rtrim($sql, ',') . ';';
        $db->query($sql);
    }

    /* 分销商品 hhk老版本去掉*/
    /*if ($_SESSION['admin_type'] == 0) {
        $hh_guest = isset($_REQUEST['hh_guest']) ? $_REQUEST['hh_guest'] : 0;
        $shop_price        = isset($_POST['mlm_shop_price']) ? (float)$_POST['mlm_shop_price'] : '';
        $money_line        = isset($_POST['mlm_money_line']) ? (float)$_POST['mlm_money_line'] : '';
        $rebate = isset($_POST['rebate']) ? (float)$_POST['rebate'] : '';
        $is_on_sale        = isset($_POST['mlm_is_on_sale']) ? $_POST['mlm_is_on_sale'] : 0;
        if ($hh_guest) {
            if (empty($shop_price) || empty($money_line) || empty($rebate)) {
                sys_msg('换换客专区：请求参数错误', 0);
            }
            if ($is_insert) {
                $sql = "INSERT INTO " . $ecs->table('goods_mlm') . " (goods_id, shop_price, money_line,rebate,is_on_sale,created_at,updated_at)" .
                    "VALUES ($goods_id, '$shop_price', '$money_line','$rebate', '$is_on_sale',".gmtime().",".gmtime().")";
                admin_log($_POST['goods_name'], 'add', 'mlm_goods');
                goods_action_log($goods_id, 0, '添加换换客商品');
            }else{
                $sel_sql = "select * from".$ecs->table('goods_mlm') . " WHERE goods_id = '$_REQUEST[goods_id]' LIMIT 1";
                $data = $db->getall($sel_sql);
                if(!empty($data)){
                    $goods_name = $exc->get_name($data[0]['goods_id']);
                    mlm_goods_edit_check($shop_price,$money_line,$rebate,$is_on_sale,$data,$goods_name);
                }else{
                    $sql = "INSERT INTO " . $ecs->table('goods_mlm') . " (goods_id, shop_price, money_line,rebate,is_on_sale,created_at,updated_at)" .
                        "VALUES ($goods_id, '$shop_price', '$money_line','$rebate', '$is_on_sale',".gmtime().",".gmtime().")";
                    admin_log($_POST['goods_name'], 'edit', 'edit_mlm_goods');
                    goods_action_log($goods_id, 7, '编辑商品加入换换客');
                }
            }
            $db->query($sql);
        }else{
            if(!$is_insert){
                $sel_sql = "select goods_id,is_on_sale from".$ecs->table('goods_mlm') . " WHERE goods_id = '$_REQUEST[goods_id]' LIMIT 1";
                $data = $db->getall($sel_sql);
                if(!empty($data) && $data[0]['is_on_sale'] == 1){
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_mlm') . " SET " .
                        "shop_price = '$shop_price', " .
                        "money_line = '$money_line', " .
                        "rebate = '$rebate', " .
                        "is_on_sale = 0 ," .
                        "updated_at = '".gmtime()."' " .
                        "WHERE goods_id = '$_REQUEST[goods_id]' LIMIT 1";
                    $db->query($sql);
                    admin_log($_POST['goods_name'], 'edit', 'sale_mlm_goods');
                    goods_action_log($goods_id, 7, '编辑商品/下架换换客商品');
                }else{
                    $sql = "INSERT INTO " . $ecs->table('goods_mlm') . " (goods_id, shop_price, money_line,rebate,is_on_sale,created_at,updated_at)" .
                        "VALUES ($goods_id, '$shop_price', '$money_line','$rebate', '$is_on_sale',".gmtime().",".gmtime().")";
                    admin_log($_POST['goods_name'], 'add', 'mlm_goods');
                    goods_action_log($goods_id, 0, '添加换换客商品');
                }
            }
        }
    }*/


    /* 处理属性 */
    if ((isset($_POST['attr_id_list']) && isset($_POST['attr_value_list'])) || (empty($_POST['attr_id_list']) && empty($_POST['attr_value_list'])))
    {
        // 取得原有的属性值
        $goods_attr_list = array();
        $keywords_arr = explode(" ", $_POST['keywords']);
        $keywords_arr = array_flip($keywords_arr);
        if (isset($keywords_arr['']))
        {
            unset($keywords_arr['']);
        }

        $sql = "SELECT attr_id, attr_index FROM " . $ecs->table('attribute') . " WHERE cat_id = '$goods_type'";

        $attr_res = $db->query($sql);

        $attr_list = array();

        while ($row = $db->fetchRow($attr_res))
        {
            $attr_list[$row['attr_id']] = $row['attr_index'];
        }
        $sql = "SELECT g.*, a.attr_type
                FROM " . $ecs->table('goods_attr') . " AS g
                    LEFT JOIN " . $ecs->table('attribute') . " AS a
                        ON a.attr_id = g.attr_id
                WHERE g.goods_id = '$goods_id'";

        $res = $db->query($sql);

        while ($row = $db->fetchRow($res))
        {
            $goods_attr_list[$row['attr_id']][$row['attr_value']] = array('sign' => 'delete', 'goods_attr_id' => $row['goods_attr_id'], 'attr_image' => $row['attr_image']);
        }
        // 循环现有的，根据原有的做相应处理
        if(isset($_POST['attr_id_list']))
        {
            foreach ($_POST['attr_id_list'] AS $key => $attr_id)
            {
                $attr_value = $_POST['attr_value_list'][$key];
                $attr_price = $_POST['attr_price_list'][$key];
                $arr = [
                    'name' => $_FILES['attr_image_list']['name'][$key],
                    'type' => $_FILES['attr_image_list']['type'][$key],
                    'tmp_name' => $_FILES['attr_image_list']['tmp_name'][$key],
                    'error' => $_FILES['attr_image_list']['error'][$key],
                    'size' => $_FILES['attr_image_list']['size'][$key],
                ];
                $thumb = reformat_image_name('goods_thumb', $goods_id, $image->upload_image($arr), 'thumb') ?: '';
                if (!empty($attr_value))
                {
                    if (isset($goods_attr_list[$attr_id][$attr_value]))
                    {
                        // 如果原来有，标记为更新
                        $goods_attr_list[$attr_id][$attr_value]['sign'] = 'update';
                        $goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
                        if(!$goods_attr_list[$attr_id][$attr_value]['attr_image'] || ($goods_attr_list[$attr_id][$attr_value]['attr_image'] && $thumb)){
                            $goods_attr_list[$attr_id][$attr_value]['attr_image'] = $thumb;
                        }
                    }
                    else
                    {
                        // 如果原来没有，标记为新增
                        $goods_attr_list[$attr_id][$attr_value]['sign'] = 'insert';
                        $goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
                        $goods_attr_list[$attr_id][$attr_value]['attr_image'] = $thumb;
                    }
                    $val_arr = explode(' ', $attr_value);
                    foreach ($val_arr AS $k => $v)
                    {
                        if (!isset($keywords_arr[$v]) && $attr_list[$attr_id] == "1")
                        {
                            $keywords_arr[$v] = $v;
                        }
                    }
                }
            }
        }
        $keywords = join(' ', array_flip($keywords_arr));

        $sql = "UPDATE " .$ecs->table('goods'). " SET keywords = '$keywords' WHERE goods_id = '$goods_id' LIMIT 1";

        $db->query($sql);

        /* 插入、更新、删除数据 */
        foreach ($goods_attr_list as $attr_id => $attr_value_list)
        {
            foreach ($attr_value_list as $attr_value => $info)
            {
                if ($info['sign'] == 'insert')
                {
                    $sql = "INSERT INTO " .$ecs->table('goods_attr'). " (attr_id, goods_id, attr_value, attr_price, attr_image)".
                            "VALUES ('$attr_id', '$goods_id', '$attr_value', '$info[attr_price]', '$info[attr_image]')";
                }
                elseif ($info['sign'] == 'update')
                {
                    $sql = "UPDATE " .$ecs->table('goods_attr'). " SET attr_price = '$info[attr_price]',attr_image = '$info[attr_image]' WHERE goods_attr_id = '$info[goods_attr_id]' LIMIT 1";
                }
                else
                {
                    $sql = "SELECT product_id  from (select goods_id,product_id,REPLACE(goods_attr,'|',',') as goods_attr from " . $GLOBALS['ecs']->table('products') . ") as p where FIND_IN_SET('".$info['goods_attr_id']."',goods_attr) and goods_id = '$goods_id' limit 1";
                    $product_id = $db->getOne($sql);
                    if($product_id > 0){
                        sys_msg("货品列表中已存在【".$attr_value."】销售属性");
                    }else{
                        $sql = "DELETE FROM " .$ecs->table('goods_attr'). " WHERE goods_attr_id = '$info[goods_attr_id]' LIMIT 1" ;
                    }

                }
                $db->query($sql);
            }
        }
    }

    /* 处理会员价格 */
    if (isset($_POST['user_rank']) && isset($_POST['user_price']))
    {
        handle_member_price($goods_id, $_POST['user_rank'], $_POST['user_price']);
    }

    /* 处理优惠价格 */
    if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
    {
        $temp_num = array_count_values($_POST['volume_number']);
        foreach($temp_num as $v)
        {
            if ($v > 1)
            {
                sys_msg($_LANG['volume_number_continuous'], 1, array(), false);
                break;
            }
        }
        handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
    }

    /* 处理扩展分类 */
    if (isset($_POST['other_cat']))
    {
        handle_other_cat($goods_id, array_unique($_POST['other_cat']));
    }

    if ($is_insert)
    {
        /* 处理关联商品 */
        handle_link_goods($goods_id);

        /* 处理组合商品 */
        handle_group_goods($goods_id);

        /* 处理关联文章 */
        handle_goods_article($goods_id);
    }

    /* 重新格式化图片名称 */
    $original_img = reformat_image_name('goods', $goods_id, $original_img, 'source');
    $goods_img = reformat_image_name('goods', $goods_id, $goods_img, 'goods');
    $goods_thumb = reformat_image_name('goods_thumb', $goods_id, $goods_thumb, 'thumb');
    if ($goods_img !== false)
    {
        $db->query("UPDATE " . $ecs->table('goods') . " SET goods_img = '$goods_img' WHERE goods_id='$goods_id'");
    }

    if ($original_img !== false)
    {
        $db->query("UPDATE " . $ecs->table('goods') . " SET original_img = '$original_img' WHERE goods_id='$goods_id'");
    }

    if ($goods_thumb !== false)
    {
        $db->query("UPDATE " . $ecs->table('goods') . " SET goods_thumb = '$goods_thumb' WHERE goods_id='$goods_id'");
    }

    /* 如果有图片，把商品图片加入图片相册 */
    if (isset($img))
    {
        /* 重新格式化图片名称 */
        if (empty($is_url_goods_img))
        {
            $img = reformat_image_name('gallery', $goods_id, $img, 'source');
            $gallery_img = reformat_image_name('gallery', $goods_id, $gallery_img, 'goods');
        }
        else
        {
            $img = $url_goods_img;
            $gallery_img = $url_goods_img;
        }

        $gallery_thumb = reformat_image_name('gallery_thumb', $goods_id, $gallery_thumb, 'thumb');
        $sql = "INSERT INTO " . $ecs->table('goods_gallery') . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                "VALUES ('$goods_id', '$gallery_img', '', '$gallery_thumb', '$img')";
        $db->query($sql);
    }

    /* 处理相册图片 */
    handle_gallery_image($goods_id, $_FILES['img_url'], $_POST['img_desc'], $_POST['img_file']);

    /* 编辑时处理相册图片描述 */
    if (!$is_insert && isset($_POST['old_img_desc']))
    {
        foreach ($_POST['old_img_desc'] AS $img_id => $img_desc)
        {
            $sql = "UPDATE " . $ecs->table('goods_gallery') . " SET img_desc = '$img_desc' WHERE img_id = '$img_id' LIMIT 1";
            $db->query($sql);
        }
    }

    /* 不保留商品原图的时候删除原图 */
    if ($proc_thumb && !$_CFG['retain_original_img'] && !empty($original_img))
    {
        $db->query("UPDATE " . $ecs->table('goods') . " SET original_img='' WHERE `goods_id`='{$goods_id}'");
        $db->query("UPDATE " . $ecs->table('goods_gallery') . " SET img_original='' WHERE `goods_id`='{$goods_id}'");
        @unlink('../' . $original_img);
        @unlink('../' . $img);
    }

    /**处理自定义属性**/
    if(isset($_POST['custom_specification_keys']) && !empty($_POST['custom_specification_keys']) && isset($_POST['custom_specification_values']) && !empty($_POST['custom_specification_values']) && count($_POST['custom_specification_values']) == count($_POST['custom_specification_keys'])){
        saveCustomSpecification($goods_id,array_combine($_POST['custom_specification_keys'],$_POST['custom_specification_values']));
    }


    /* 记录上一次选择的分类和品牌 */
    setcookie('ECSCP[last_choose]', $catgory_id . '|' . $brand_id, gmtime() + 86400, NULL, NULL, NULL, TRUE);
    /* 清空缓存 */
    clear_cache_files();

    /* 是否有货品 */
    $specifications_list = get_goods_specifications_list($goods_id);
    $product_list_url = $GLOBALS['ecs']->url()."admin/goods.php?act=product_list&goods_id=".$goods_id."&status=".$_REQUEST['status'];
    if($specifications_list){
        echo '<script type="text/javascript">window.location.href="'.$product_list_url.'";</script>';exit;
    }
    /* 提示页面 */
    $link = array();
    if (check_goods_specifications_exist($goods_id) && $specifications_list)
    {
        $link[0] = array('href' => 'goods.php?act=product_list&goods_id=' . $goods_id, 'text' => $_LANG['product']);
    }
    if ($code == 'virtual_card')
    {
        $link[1] = array('href' => 'virtual_card.php?act=replenish&goods_id=' . $goods_id, 'text' => $_LANG['add_replenish']);
    }
    if ($is_insert)
    {
        $link[2] = add_link($code);
    }
    $link[3] = list_link($is_insert, $code);


    //$key_array = array_keys($link);
    for($i=0;$i<count($link);$i++)
    {
       $key_array[]=$i;
    }
    krsort($link);
    $link = array_combine($key_array, $link);


    sys_msg($is_insert ? $_LANG['add_goods_ok'] : $_LANG['edit_goods_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch')
{
    $code = empty($_REQUEST['extension_code'])? '' : trim($_REQUEST['extension_code']);

    /* 取得要操作的商品编号 */
    $goods_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
    if (isset($_POST['type']))
    {
        $user_id = $_SESSION['admin_id'];
        $time = gmtime();
        /* 放入回收站 */
        if ($_POST['type'] == 'trash')
        {
            /* 检查权限 */
            admin_priv('remove_back');

            update_goods($goods_id, 'is_delete', '1');

            /* 记录日志 */
            admin_log('', 'batch_trash', 'goods');
        }
        /* 上架 */
        elseif ($_POST['type'] == 'on_sale')
        {
            /* 检查权限 */
            admin_priv('goods_on_sale');
            $sql = "select goods_id,goods_name,is_check,first_sale_time,is_on_sale from " . $ecs->table('goods') . " where " .db_create_in($goods_id,'goods_id');
            $goods = $db->getAll($sql);
            $first_arr = $arr = $success = [];
            foreach ($goods as $val) {
                if ($val['is_check'] != 2 || $val['is_on_sale'] != 0) {
                    sys_msg('商品状态异常:'.$val['goods_id']);
                }
                if(empty($val['first_sale_time'])){
                    $first_arr[] = $val['goods_id'];
                }else{
                    $arr[] = $val['goods_id'];
                }
            }
            $success = array_merge($first_arr,$arr);
            $sql = "update " . $ecs->table('goods') . " set is_on_sale = 1 , sale_time = " . gmtime() . " where " . db_create_in($success, 'goods_id');
            if (!$db->query($sql)) sys_msg('上架失败');
            if (!empty($first_arr)) {
                $sql = "update " . $ecs->table('goods') . " set is_on_sale = 1,first_sale_time = " . gmtime() . " where " . db_create_in($first_arr, 'goods_id');
                $db->query($sql);
            }

            admin_log(implode(',',$success), 'in_on_sale', 'goods');
            foreach ($success as $val){
                goods_action_log($val,4,'上架');
            }
            sys_msg('批量上架成功');
        }

        /* 下架 */
        elseif ($_POST['type'] == 'not_on_sale')
        {
            /* 检查权限*/
            admin_priv('goods_sale_out');
            $arr = explode(',',$goods_id);
            $sql = "select goods_id from " . $ecs->table('goods') . " where is_on_sale = 1 and " . db_create_in($arr,'goods_id');
            $goodsModel = $db->getAll($sql);;
            $goods = array_column($goodsModel,'goods_id');
            $error = array_diff($arr,$goods);
            if (!empty($error)) {
                sys_msg('商品状态异常：'.implode(',',$error));
            }
            $sql = "update " . $ecs->table('goods') . " set is_on_sale = 0,sale_time = " . gmtime() . " where " . db_create_in($arr, 'goods_id');
            if (!$db->query($sql)){
                sys_msg('下架失败');
            }

            $remark = $_REQUEST['no_on_sale_reason'] ? htmlspecialchars(trim($_REQUEST['no_on_sale_reason'])): '下架';
            $remark = '下架：'.$remark;
            admin_log(addslashes($goods_id), 'in_no_sale', 'goods');
            foreach ($arr as $v){
                goods_action_log($v,5,$remark);
            }
        }

        /* 设为精品 */
        elseif ($_POST['type'] == 'best')
        {
            /* 检查权限 */
            admin_priv('goods_manage');
            update_goods($goods_id, 'is_best', '1');
        }

        /* 取消精品 */
        elseif ($_POST['type'] == 'not_best')
        {
            /* 检查权限 */
            admin_priv('goods_manage');
            update_goods($goods_id, 'is_best', '0');
        }

        /* 设为新品 */
        elseif ($_POST['type'] == 'new')
        {
            /* 检查权限 */
            admin_priv('goods_manage');
            update_goods($goods_id, 'is_new', '1');
        }

        /* 取消新品 */
        elseif ($_POST['type'] == 'not_new')
        {
            /* 检查权限 */
            admin_priv('goods_manage');
            update_goods($goods_id, 'is_new', '0');
        }

        /* 设为热销 */
        elseif ($_POST['type'] == 'hot')
        {
            /* 检查权限 */
            admin_priv('goods_manage');
            update_goods($goods_id, 'is_hot', '1');
        }

        /* 取消热销 */
        elseif ($_POST['type'] == 'not_hot')
        {
            /* 检查权限 */
            admin_priv('goods_manage');
            update_goods($goods_id, 'is_hot', '0');
        }

        /* 转移到分类 */
        elseif ($_POST['type'] == 'move_to')
        {
            /* 检查权限 */
            admin_priv('goods_manage');
            update_goods($goods_id, 'cat_id', $_POST['target_cat']);
        }

        /* 转移到供货商 */
        elseif ($_POST['type'] == 'suppliers_move_to')
        {
            /* 检查权限 */
            admin_priv('goods_manage');
            update_goods($goods_id, 'suppliers_id', $_POST['suppliers_id']);
        }

        /* 还原 */
        elseif ($_POST['type'] == 'restore')
        {
            /* 检查权限 */
            admin_priv('remove_back');

            update_goods($goods_id, 'is_delete', '0');

            /* 记录日志 */
            admin_log('', 'batch_restore', 'goods');
        }
        /* 删除 */
        elseif ($_POST['type'] == 'drop')
        {
            /* 检查权限 */
            admin_priv('remove_back');

            delete_goods($goods_id);

            /* 记录日志 */
            admin_log('', 'batch_remove', 'goods');
        }
        /* 提审 */
        elseif ($_POST['type'] == 'submit_audit')
        {
            admin_priv('goods_manage');
            $arr = explode(',',$goods_id);
            $sql = "update " . $ecs->table('goods') . " set is_check = 1,check_time = " . gmtime() . " where " . db_create_in($arr, 'goods_id');
            if (!$db->query($sql)){
                sys_msg('提审失败');
            }
            admin_log($goods_id, 'in_audit', 'goods');
            foreach ($arr as $v){
                goods_action_log($v,1,'提交审核');
            }
        }
    }

    /* 清除缓存 */
    clear_cache_files();

    if ($_POST['type'] == 'drop' || $_POST['type'] == 'restore')
    {
        $link[] = array('href' => 'goods.php?act=trash', 'text' => $_LANG['11_goods_trash']);
    }
    elseif ($_POST['type'] == 'on_sale' || $_POST['type'] == 'not_on_sale')
    {
        $link[] = array('href' => 'goods.php?act=list&uselastfilter=1','text'=>$_LANG['back_goods_list']);
    }
    else
    {
        $link[] = list_link(true, $code);
    }
    sys_msg($_LANG['batch_handle_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 显示图片
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'show_image')
{

    if (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)
    {
        $img_url = $_GET['img_url'];
    }
    else
    {
        if (strpos($_GET['img_url'], 'http://') === 0 || strpos($_GET['img_url'], 'https://') === 0)
        {
            $img_url = $_GET['img_url'];
        }
        else
        {
            $img_url = '../' . $_GET['img_url'];
        }
    }
    $smarty->assign('img_url', $img_url);
    $smarty->display('goods_show_image.htm');
}

/*------------------------------------------------------ */
//-- 修改商品名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_goods_name')
{
    check_authz_json('goods_manage');

    $goods_id   = intval($_POST['id']);
    $goods_name = json_str_iconv(trim($_POST['val']));

    if ($exc->edit("goods_name = '$goods_name', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result(stripslashes($goods_name));
    }
}

/*------------------------------------------------------ */
//-- 修改商品货号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_goods_sn')
{
    check_authz_json('goods_manage');

    $goods_id = intval($_POST['id']);
    $goods_sn = json_str_iconv(trim($_POST['val']));

    /* 检查是否重复 */
    if (!$exc->is_only('goods_sn', $goods_sn, $goods_id))
    {
        make_json_error($_LANG['goods_sn_exists']);
    }
    $sql="SELECT goods_id FROM ". $ecs->table('products')."WHERE product_sn='$goods_sn'";
    if($db->getOne($sql))
    {
        make_json_error($_LANG['goods_sn_exists']);
    }
    if ($exc->edit("goods_sn = '$goods_sn', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result(stripslashes($goods_sn));
    }
}

elseif ($_REQUEST['act'] == 'check_goods_sn')
{
    check_authz_json('goods_manage');

    $goods_id = intval($_REQUEST['goods_id']);
    $goods_sn = htmlspecialchars(json_str_iconv(trim($_REQUEST['goods_sn'])));

    /* 检查是否重复 */
    if (!$exc->is_only('goods_sn', $goods_sn, $goods_id))
    {
        make_json_error($_LANG['goods_sn_exists']);
    }
    if(!empty($goods_sn))
    {
        $sql="SELECT goods_id FROM ". $ecs->table('products')."WHERE product_sn='$goods_sn'";
        if($db->getOne($sql))
        {
            make_json_error($_LANG['goods_sn_exists']);
        }
    }
    make_json_result('');
}
elseif ($_REQUEST['act'] == 'check_products_goods_sn')
{
    check_authz_json('goods_manage');

    $goods_id = intval($_REQUEST['goods_id']);
    $goods_sn = json_str_iconv(trim($_REQUEST['goods_sn']));
    $products_sn=explode('||',$goods_sn);
    if(!is_array($products_sn))
    {
        make_json_result('');
    }
    else
    {
        foreach ($products_sn as $val)
        {
            if(empty($val))
            {
                 continue;
            }
            if(is_array($int_arry))
            {
                if(in_array($val,$int_arry))
                {
                     make_json_error($val.$_LANG['goods_sn_exists']);
                }
            }
            $int_arry[]=$val;
            if (!$exc->is_only('goods_sn', $val, '0'))
            {
                make_json_error($val.$_LANG['goods_sn_exists']);
            }
            $sql="SELECT goods_id FROM ". $ecs->table('products')."WHERE product_sn='$val'";
            if($db->getOne($sql))
            {
                make_json_error($val.$_LANG['goods_sn_exists']);
            }
        }
    }
    /* 检查是否重复 */
    make_json_result('');
}

/*------------------------------------------------------ */
//-- 修改商品价格
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_goods_price')
{
    check_authz_json('goods_manage');

    $goods_id       = intval($_POST['id']);
    $goods_price    = floatval($_POST['val']);
    $price_rate     = floatval($_CFG['market_price_rate'] * $goods_price);

    if ($goods_price < 0 || $goods_price == 0 && $_POST['val'] != "$goods_price")
    {
        make_json_error($_LANG['shop_price_invalid']);
    }
    else
    {
        if ($exc->edit("shop_price = '$goods_price', market_price = '$price_rate', last_update=" .gmtime(), $goods_id))
        {
            clear_cache_files();
            make_json_result(number_format($goods_price, 2, '.', ''));
        }
    }
}

/*------------------------------------------------------ */
//-- 修改商品库存数量
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_goods_number')
{
    check_authz_json('goods_manage');

    $goods_id   = intval($_POST['id']);
    $goods_num  = intval($_POST['val']);

    if($goods_num < 0 || $goods_num == 0 && $_POST['val'] != "$goods_num")
    {
        make_json_error($_LANG['goods_number_error']);
    }

    if(check_goods_product_exist($goods_id) == 1)
    {
        make_json_error($_LANG['sys']['wrong'] . $_LANG['cannot_goods_number']);
    }

    if ($exc->edit("goods_number = '$goods_num', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result($goods_num);
    }
}

/*------------------------------------------------------ */
//-- 修改上架状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_on_sale')
{
    $goods_id       = intval($_POST['id']);
    $on_sale        = intval($_POST['val']);
    $sale_time      = gmtime();
    $is_pre_sale    = 0;
    if($_POST['sale_time']){
        $sale_time  = strtotime($_POST['sale_time']);
        $is_pre_sale = 1;
    }

     if($on_sale){
        check_authz_json('goods_on_sale');
    }else{
        check_authz_json('goods_sale_out');
    }

    if ($exc->edit("is_on_sale = '$on_sale', is_pre_sale = $is_pre_sale , last_update=" .gmtime().",sale_time = " .$sale_time, $goods_id))
    {
        $user_id = $_SESSION['admin_id'] ?  $_SESSION['admin_id'] : '';
        $time = gmtime();
        if($on_sale){
            $first_sale_time = $exc->get_name($goods_id, 'first_sale_time');
            if(empty($first_sale_time)){
                $exc->edit("first_sale_time = " .gmtime(), $goods_id);
            }
            $content = $is_pre_sale?":预售(".date('Y-m-d H:i:s',$sale_time).")":'';
            goods_action_log($goods_id,4,'上架'.$content);
            $goods_name = $exc->get_name($goods_id);
            admin_log(addslashes($goods_name), 'in_on_sale', 'goods');
            make_json_result($on_sale,'商品已上架');
        }else{
            $reason = $_REQUEST['reason'] ? htmlspecialchars(trim($_REQUEST['reason'])) :  "下架";
            goods_action_log($goods_id,5,'下架：'.$reason);
            $goods_name = $exc->get_name($goods_id);
            admin_log(addslashes($goods_name), 'in_no_sale', 'goods');
            //老版本hhk去掉
            /*$sql = "select is_on_sale from " . $GLOBALS['ecs']->table('goods_mlm') . " where goods_id = '$goods_id'";
            $is_on_sale = $db->getOne($sql);
            if($is_on_sale){
                $sql = "UPDATE " . $ecs->table('goods_mlm') . " SET " .
                    "is_on_sale = 0 " .
                    "WHERE goods_id = '$goods_id' LIMIT 1";
                $db->query($sql);
                admin_log($goods_name, 'edit', 'sale_mlm_goods');
                goods_action_log($goods_id, 7, '编辑商品/下架换换客商品');
            }*/
            make_json_result($on_sale,'商品已下架');

        }
        clear_cache_files();

    }
}

/*------------------------------------------------------ */
//-- 修改精品推荐状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_best')
{
    check_authz_json('goods_manage');

    $goods_id       = intval($_POST['id']);
    $is_best        = intval($_POST['val']);

    if ($exc->edit("is_best = '$is_best', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result($is_best);
    }
}

/*------------------------------------------------------ */
//-- 修改新品推荐状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_new')
{
    check_authz_json('goods_manage');

    $goods_id       = intval($_POST['id']);
    $is_new         = intval($_POST['val']);

    if ($exc->edit("is_new = '$is_new', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result($is_new);
    }
}

/*------------------------------------------------------ */
//-- 修改热销推荐状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_hot')
{
    check_authz_json('goods_manage');

    $goods_id       = intval($_POST['id']);
    $is_hot         = intval($_POST['val']);

    if ($exc->edit("is_hot = '$is_hot', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result($is_hot);
    }
}

/*------------------------------------------------------ */
//-- 修改商品排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('goods_manage');

    $goods_id       = intval($_POST['id']);
    $sort_order     = intval($_POST['val']);

    if ($exc->edit("sort_order = '$sort_order', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result($sort_order);
    }
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $is_delete = empty($_REQUEST['is_delete']) ? 0 : intval($_REQUEST['is_delete']);
    $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
    $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : '';
    $where = '';
    if($status !== '' && $is_delete == 0){
        switch ($status){
            case 0 :
                $where .= " and is_check = 0";
                break;
            case 1 :
                $where .= " and is_check = 1";
                break;
            case 2 :
                $where .= " and is_check = 2";
                break;
            case 3 :
                $where .= " and is_check = 3";
                break;
            case 4 :
                $where .= " and g.is_on_sale = 1 and is_check = 2";
                break;
            case 5 :
                $where .= " and g.is_on_sale = 0 and is_check = 2";
                break;
            case 6 :
                $where .= " and goods_number = 0 and is_check = 2";
                break;
        }
    }
    $goods_list = goods_list($is_delete, ($code=='') ? 1 : 0, $where);
    $goods_list['filter']['status'] = (string)$status;
    $smarty->assign('status', $status);
    $smarty->assign('admin_type', $_SESSION['admin_type']);

    $handler_list = array();
    $handler_list['virtual_card'][] = array('url'=>'virtual_card.php?act=card', 'title'=>$_LANG['card'], 'img'=>'icon_send_bonus.gif');
    $handler_list['virtual_card'][] = array('url'=>'virtual_card.php?act=replenish', 'title'=>$_LANG['replenish'], 'img'=>'icon_add.svg');
    $handler_list['virtual_card'][] = array('url'=>'virtual_card.php?act=batch_card_add', 'title'=>$_LANG['batch_card_add'], 'img'=>'icon_output.gif');

    if (isset($handler_list[$code]))
    {
        $smarty->assign('add_handler',      $handler_list[$code]);
    }
    $smarty->assign('code',         $code);
    $smarty->assign('goods_list',   $goods_list['goods']);
    $smarty->assign('filter',       $goods_list['filter']);
    $smarty->assign('record_count', $goods_list['record_count']);
    $smarty->assign('page_count',   $goods_list['page_count']);
    $smarty->assign('list_type',    $is_delete ? 'trash' : 'goods');
    $smarty->assign('use_storage',  empty($_CFG['use_storage']) ? 0 : 1);

    /* 排序标记 */
    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 获取商品类型存在规格的类型 */
    $specifications = get_goods_type_specifications();
    $smarty->assign('specifications', $specifications);

    $tpl = $is_delete ? 'goods_trash.htm' : 'goods_list.htm';

    make_json_result($smarty->fetch($tpl), '',
        array('filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']));
}

/*------------------------------------------------------ */
//-- 放入回收站
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    $goods_id = intval($_REQUEST['id']);

    /* 检查权限 */
    check_authz_json('remove_back');

    if ($exc->edit("is_delete = 1", $goods_id))
    {
        clear_cache_files();
        $goods_name = $exc->get_name($goods_id);

        admin_log(addslashes($goods_name), 'trash', 'goods'); // 记录日志
        goods_action_log($goods_id, 0, '删除商品');

        //hhk老版本去掉
        /*$sql = "select is_on_sale from " . $GLOBALS['ecs']->table('goods_mlm') . " where goods_id = '$goods_id'";
        $is_on_sale = $db->getOne($sql);
        if($is_on_sale){
            $sql = "UPDATE " . $ecs->table('goods_mlm') . " SET " .
                "is_on_sale = 0 " .
                "WHERE goods_id = '$goods_id' LIMIT 1";
            $db->query($sql);
            admin_log($goods_name, 'edit', 'sale_mlm_goods');
            goods_action_log($goods_id, 7, '编辑商品/下架换换客商品');
        }*/
        $url = 'goods.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

        ecs_header("Location: $url\n");
        exit;
    }
}

/*------------------------------------------------------ */
//-- 还原回收站中的商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'restore_goods')
{
    $goods_id = intval($_REQUEST['id']);

    check_authz_json('remove_back'); // 检查权限

    $exc->edit("is_delete = 0, add_time = '" . gmtime() . "'", $goods_id);
    clear_cache_files();

    $goods_name = $exc->get_name($goods_id);

    admin_log(addslashes($goods_name), 'restore', 'goods'); // 记录日志
    goods_action_log($goods_id, 0, '还原商品');

    $url = 'goods.php?act=query&' . str_replace('act=restore_goods', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 彻底删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_goods')
{
    // 检查权限
    check_authz_json('remove_back');

    // 取得参数
    $goods_id = intval($_REQUEST['id']);
    if ($goods_id <= 0)
    {
        make_json_error('invalid params');
    }

    /* 取得商品信息 */
    $sql = "SELECT goods_id, goods_name, is_delete, is_real, goods_thumb, " .
                "goods_img, original_img " .
            "FROM " . $ecs->table('goods') .
            " WHERE goods_id = '$goods_id'";
    $goods = $db->getRow($sql);
    if (empty($goods))
    {
        make_json_error($_LANG['goods_not_exist']);
    }

    if ($goods['is_delete'] != 1)
    {
        make_json_error($_LANG['goods_not_in_recycle_bin']);
    }

    /* 删除商品图片和轮播图片 */
    if (!empty($goods['goods_thumb']))
    {
        @unlink('../' . $goods['goods_thumb']);
    }
    if (!empty($goods['goods_img']))
    {
        @unlink('../' . $goods['goods_img']);
    }
    if (!empty($goods['original_img']))
    {
        @unlink('../' . $goods['original_img']);
    }
    /* 删除商品 */
    $exc->drop($goods_id);

    /* 删除商品的货品记录 */
    $sql = "DELETE FROM " . $ecs->table('products') .
            " WHERE goods_id = '$goods_id'";
    $db->query($sql);

    /* 记录日志 */
    admin_log(addslashes($goods['goods_name']), 'remove', 'goods');
    goods_action_log($goods_id, 0, '清空商品');

    /* 删除商品相册 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            "FROM " . $ecs->table('goods_gallery') .
            " WHERE goods_id = '$goods_id'";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        if (!empty($row['img_url']))
        {
            @unlink('../' . $row['img_url']);
        }
        if (!empty($row['thumb_url']))
        {
            @unlink('../' . $row['thumb_url']);
        }
        if (!empty($row['img_original']))
        {
            @unlink('../' . $row['img_original']);
        }
    }

    $sql = "DELETE FROM " . $ecs->table('goods_gallery') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);

    /* 删除相关表记录 */
    $sql = "DELETE FROM " . $ecs->table('collect_goods') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('goods_article') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('goods_attr') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('goods_cat') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('member_price') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('group_goods') . " WHERE parent_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('group_goods') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('link_goods') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('link_goods') . " WHERE link_goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('tag') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('comment') . " WHERE comment_type = 0 AND id_value = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('collect_goods') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('booking_goods') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . $ecs->table('goods_activity') . " WHERE goods_id = '$goods_id'";
    $db->query($sql);

    /* 如果不是实体商品，删除相应虚拟商品记录 */
    if ($goods['is_real'] != 1)
    {
        $sql = "DELETE FROM " . $ecs->table('virtual_card') . " WHERE goods_id = '$goods_id'";
        if (!$db->query($sql, 'SILENT') && $db->errno() != 1146)
        {
            die($db->error());
        }
    }

    clear_cache_files();
    $url = 'goods.php?act=query&' . str_replace('act=drop_goods', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");

    exit;
}

/*------------------------------------------------------ */
//-- 切换商品类型
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'get_attr')
{
    check_authz_json('goods_manage');

    $goods_id   = empty($_GET['goods_id']) ? 0 : intval($_GET['goods_id']);
    $goods_type = empty($_GET['goods_type']) ? 0 : intval($_GET['goods_type']);
    $category_id = empty($_GET['category_id']) ? 0 : intval($_GET['category_id']);

    $content    = build_attr_html($goods_type, $goods_id, $category_id);

    make_json_result($content);
}

/*------------------------------------------------------ */
//-- 删除图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_image')
{
    check_authz_json('goods_manage');

    $img_id = empty($_REQUEST['img_id']) ? 0 : intval($_REQUEST['img_id']);

    /* 删除图片文件 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            " FROM " . $GLOBALS['ecs']->table('goods_gallery') .
            " WHERE img_id = '$img_id'";
    $row = $GLOBALS['db']->getRow($sql);

    if ($row['img_url'] != '' && is_file('../' . $row['img_url']))
    {
        @unlink('../' . $row['img_url']);
    }
    if ($row['thumb_url'] != '' && is_file('../' . $row['thumb_url']))
    {
        @unlink('../' . $row['thumb_url']);
    }
    if ($row['img_original'] != '' && is_file('../' . $row['img_original']))
    {
        @unlink('../' . $row['img_original']);
    }

    /* 删除数据 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_gallery') . " WHERE img_id = '$img_id' LIMIT 1";
    $GLOBALS['db']->query($sql);

    clear_cache_files();
    make_json_result($img_id);
}

/*------------------------------------------------------ */
//-- 搜索商品，仅返回名称及ID
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'get_goods_list')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_goods_list($filters);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => $val['shop_price']);
    }

    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 把商品加入关联
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add_link_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('goods_manage');

    $linked_array   = $json->decode($_GET['add_ids']);
    $linked_goods   = $json->decode($_GET['JSON']);
    $goods_id       = $linked_goods[0];
    $is_double      = $linked_goods[1] == true ? 0 : 1;

    foreach ($linked_array AS $val)
    {
        if ($is_double)
        {
            /* 双向关联 */
            $sql = "INSERT INTO " . $ecs->table('link_goods') . " (goods_id, link_goods_id, is_double, admin_id) " .
                    "VALUES ('$val', '$goods_id', '$is_double', '$_SESSION[admin_id]')";
            $db->query($sql, 'SILENT');
        }

        $sql = "INSERT INTO " . $ecs->table('link_goods') . " (goods_id, link_goods_id, is_double, admin_id) " .
                "VALUES ('$goods_id', '$val', '$is_double', '$_SESSION[admin_id]')";
        $db->query($sql, 'SILENT');
    }

    $linked_goods   = get_linked_goods($goods_id);
    $options        = array();

    foreach ($linked_goods AS $val)
    {
        $options[] = array('value'  => $val['goods_id'],
                        'text'      => $val['goods_name'],
                        'data'      => '');
    }

    clear_cache_files();
    make_json_result($options);
}

/*------------------------------------------------------ */
//-- 删除关联商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_link_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('goods_manage');

    $drop_goods     = $json->decode($_GET['drop_ids']);
    $drop_goods_ids = db_create_in($drop_goods);
    $linked_goods   = $json->decode($_GET['JSON']);
    $goods_id       = $linked_goods[0];
    $is_signle      = $linked_goods[1];

    if (!$is_signle)
    {
        $sql = "DELETE FROM " .$ecs->table('link_goods') .
                " WHERE link_goods_id = '$goods_id' AND goods_id " . $drop_goods_ids;
    }
    else
    {
        $sql = "UPDATE " .$ecs->table('link_goods') . " SET is_double = 0 ".
                " WHERE link_goods_id = '$goods_id' AND goods_id " . $drop_goods_ids;
    }
    if ($goods_id == 0)
    {
        $sql .= " AND admin_id = '$_SESSION[admin_id]'";
    }
    $db->query($sql);

    $sql = "DELETE FROM " .$ecs->table('link_goods') .
            " WHERE goods_id = '$goods_id' AND link_goods_id " . $drop_goods_ids;
    if ($goods_id == 0)
    {
        $sql .= " AND admin_id = '$_SESSION[admin_id]'";
    }
    $db->query($sql);

    $linked_goods = get_linked_goods($goods_id);
    $options      = array();

    foreach ($linked_goods AS $val)
    {
        $options[] = array(
                        'value' => $val['goods_id'],
                        'text'  => $val['goods_name'],
                        'data'  => '');
    }

    clear_cache_files();
    make_json_result($options);
}

/*------------------------------------------------------ */
//-- 增加一个配件
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add_group_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('goods_manage');

    $fittings   = $json->decode($_GET['add_ids']);
    $arguments  = $json->decode($_GET['JSON']);
    $goods_id   = $arguments[0];
    $price      = $arguments[1];

    foreach ($fittings AS $val)
    {
        $sql = "INSERT INTO " . $ecs->table('group_goods') . " (parent_id, goods_id, goods_price, admin_id) " .
                "VALUES ('$goods_id', '$val', '$price', '$_SESSION[admin_id]')";
        $db->query($sql, 'SILENT');
    }

    $arr = get_group_goods($goods_id);
    $opt = array();

    foreach ($arr AS $val)
    {
        $opt[] = array('value'      => $val['goods_id'],
                        'text'      => $val['goods_name'],
                        'data'      => '');
    }

    clear_cache_files();
    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 删除一个配件
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'drop_group_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('goods_manage');

    $fittings   = $json->decode($_GET['drop_ids']);
    $arguments  = $json->decode($_GET['JSON']);
    $goods_id   = $arguments[0];
    $price      = $arguments[1];

    $sql = "DELETE FROM " .$ecs->table('group_goods') .
            " WHERE parent_id='$goods_id' AND " .db_create_in($fittings, 'goods_id');
    if ($goods_id == 0)
    {
        $sql .= " AND admin_id = '$_SESSION[admin_id]'";
    }
    $db->query($sql);

    $arr = get_group_goods($goods_id);
    $opt = array();

    foreach ($arr AS $val)
    {
        $opt[] = array('value'      => $val['goods_id'],
                        'text'      => $val['goods_name'],
                        'data'      => '');
    }

    clear_cache_files();
    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 搜索文章
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'get_article_list')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters =(array) $json->decode(json_str_iconv($_GET['JSON']));

    $where = " WHERE cat_id > 0 ";
    if (!empty($filters['title']))
    {
        $keyword  = trim($filters['title']);
        $where   .=  " AND title LIKE '%" . mysql_like_quote($keyword) . "%' ";
    }

    $sql        = 'SELECT article_id, title FROM ' .$ecs->table('article'). $where.
                  'ORDER BY article_id DESC LIMIT 50';
    $res        = $db->query($sql);
    $arr        = array();

    while ($row = $db->fetchRow($res))
    {
        $arr[]  = array('value' => $row['article_id'], 'text' => $row['title'], 'data'=>'');
    }

    make_json_result($arr);
}

/*------------------------------------------------------ */
//-- 添加关联文章
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add_goods_article')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('goods_manage');

    $articles   = $json->decode($_GET['add_ids']);
    $arguments  = $json->decode($_GET['JSON']);
    $goods_id   = $arguments[0];

    foreach ($articles AS $val)
    {
        $sql = "INSERT INTO " . $ecs->table('goods_article') . " (goods_id, article_id, admin_id) " .
                "VALUES ('$goods_id', '$val', '$_SESSION[admin_id]')";
        $db->query($sql);
    }

    $arr = get_goods_articles($goods_id);
    $opt = array();

    foreach ($arr AS $val)
    {
        $opt[] = array('value'      => $val['article_id'],
                        'text'      => $val['title'],
                        'data'      => '');
    }

    clear_cache_files();
    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 删除关联文章
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_goods_article')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('goods_manage');

    $articles   = $json->decode($_GET['drop_ids']);
    $arguments  = $json->decode($_GET['JSON']);
    $goods_id   = $arguments[0];

    $sql = "DELETE FROM " .$ecs->table('goods_article') . " WHERE " . db_create_in($articles, "article_id") . " AND goods_id = '$goods_id'";
    $db->query($sql);

    $arr = get_goods_articles($goods_id);
    $opt = array();

    foreach ($arr AS $val)
    {
        $opt[] = array('value'      => $val['article_id'],
                        'text'      => $val['title'],
                        'data'      => '');
    }

    clear_cache_files();
    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 货品列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'product_list')
{
    admin_priv('goods_manage');

    /* 是否存在商品id */
    if (empty($_GET['goods_id']))
    {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['cannot_found_goods']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    }
    else
    {
        $goods_id = intval($_GET['goods_id']);
    }

    /* 取出商品信息 */
    $sql = "SELECT goods_sn, goods_name, goods_type, shop_price FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'";
    $goods = $db->getRow($sql);
    if (empty($goods))
    {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    }
    $smarty->assign('sn', sprintf($_LANG['good_goods_sn'], $goods['goods_sn']));
    $smarty->assign('price', sprintf($_LANG['good_shop_price'], $goods['shop_price']));
    $smarty->assign('goods_name', sprintf($_LANG['products_title'], $goods['goods_name']));
    $smarty->assign('goods_sn', sprintf($_LANG['products_title_2'], $goods['goods_sn']));


    /* 获取商品规格列表 */
    $attribute = get_goods_specifications_list($goods_id);

    if (empty($attribute))
    {
        $link[] = array('href' => 'goods.php?act=edit&goods_id=' . $goods_id, 'text' => $_LANG['edit_goods']);
        sys_msg($_LANG['not_exist_goods_attr'], 1, $link);
    }

    foreach ($attribute as $attribute_value)
    {
        //转换成数组
        $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
        $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
        $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
        $new_attr[$attribute_value['attr_id']][$attribute_value['goods_attr_id']] = $attribute_value['attr_value'];
    }

    $attribute_count = count($_attribute);

    $smarty->assign('attribute_count',          $attribute_count);
    $smarty->assign('attribute_count_3',        ($attribute_count + 3));
    $smarty->assign('product_sn',               $goods['goods_sn'] . '_');
    $smarty->assign('product_number',           $_CFG['default_storage']);

    /* 取商品的货品 */
    $product = product_list($goods_id, '');
    //保证属性排序正确
    $attr_list = array();
    foreach($product['product'] as  $item){
        foreach($item['goods_attr'] as $k => $attr){
            $attr_list[] = $attr;
        }
    }

    foreach ($product['product'] as $k => $v){
        foreach ($new_attr as $key => $val){
             $diff = array_diff($val,$v['goods_attr']);
             if(count($diff) == count($val)){
                 $html = "<select name='new_attr_$v[product_id][]'><option value='' selected>".$GLOBALS['_LANG']['select_please']."</option>";
                 foreach ($diff as $k1 => $v1){
                     $html .= "<option value='$k1'>".$v1."</option>";
                 }
                 $html .= "</select>";
                 $product['product'][$k]['goods_attr'][] = $html;
             }
        }
    }
    $insert_stock = empty($product['product']) ? true : false;
    $smarty->assign('insert_stock',                $insert_stock);
    $smarty->assign('attribute',                $_attribute);
    $smarty->assign('ur_here',      $_LANG['18_product_list']);
    $smarty->assign('action_link',  array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']));
    $smarty->assign('product_list', $product['product']);
    $smarty->assign('product_null', empty($product['product']) ? 0 : 1);
    $smarty->assign('use_storage',  empty($_CFG['use_storage']) ? 0 : 1);
    $smarty->assign('goods_id',     $goods_id);
    $smarty->assign('filter',       $product['filter']);
    $smarty->assign('full_page',    1);
    $smarty->assign('status', isset($_REQUEST['status']) ? $_REQUEST['status'] : '');

    /* 显示商品列表页面 */
    assign_query_info();

    $smarty->display('product_info_new.htm');
}

/*------------------------------------------------------ */
//-- 货品排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'product_query')
{
    /* 是否存在商品id */
    if (empty($_REQUEST['goods_id']))
    {
        make_json_error($_LANG['sys']['wrong'] . $_LANG['cannot_found_goods']);
    }
    else
    {
        $goods_id = intval($_REQUEST['goods_id']);
    }

    /* 取出商品信息 */
    $sql = "SELECT goods_sn, goods_name, goods_type, shop_price FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'";
    $goods = $db->getRow($sql);
    if (empty($goods))
    {
        make_json_error($_LANG['sys']['wrong'] . $_LANG['cannot_found_goods']);
    }
    $smarty->assign('sn', sprintf($_LANG['good_goods_sn'], $goods['goods_sn']));
    $smarty->assign('price', sprintf($_LANG['good_shop_price'], $goods['shop_price']));
    $smarty->assign('goods_name', sprintf($_LANG['products_title'], $goods['goods_name']));
    $smarty->assign('goods_sn', sprintf($_LANG['products_title_2'], $goods['goods_sn']));


    /* 获取商品规格列表 */
    $attribute = get_goods_specifications_list($goods_id);
    if (empty($attribute))
    {
        make_json_error($_LANG['sys']['wrong'] . $_LANG['cannot_found_goods']);
    }
    foreach ($attribute as $attribute_value)
    {
        //转换成数组
        $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
        $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
        $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
        $new_attr[$attribute_value['attr_id']][$attribute_value['goods_attr_id']] = $attribute_value['attr_value'];
    }
    $attribute_count = count($_attribute);

    $smarty->assign('attribute_count',          $attribute_count);
    $smarty->assign('attribute',                $_attribute);
    $smarty->assign('attribute_count_3',        ($attribute_count + 3));
    $smarty->assign('product_sn',               $goods['goods_sn'] . '_');
    $smarty->assign('product_number',           $_CFG['default_storage']);

    /* 取商品的货品 */
    $product = product_list($goods_id, '');

    $attr_list = array();
    foreach($product['product'] as  $item){
        foreach($item['goods_attr'] as $k => $attr){
            $attr_list[] = $attr;
        }
    }

    foreach ($product['product'] as $k => $v){
        foreach ($new_attr as $key => $val){
            $diff = array_diff($val,$v['goods_attr']);
            if(count($diff) == count($val)){
                $html = "<select name='new_attr_$v[product_id][]'><option value='' selected>".$GLOBALS['_LANG']['select_please']."</option>";
                foreach ($diff as $k1 => $v1){
                    $html .= "<option value='$k1'>".$v1."</option>";
                }
                $html .= "</select>";
                $product['product'][$k]['goods_attr'][] = $html;
            }
        }
    }

    $smarty->assign('ur_here', $_LANG['18_product_list']);
    $smarty->assign('action_link', array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']));
    $smarty->assign('product_list',  $product['product']);
    $smarty->assign('use_storage',  empty($_CFG['use_storage']) ? 0 : 1);
    $smarty->assign('goods_id',    $goods_id);
    $smarty->assign('filter',       $product['filter']);

    /* 排序标记 */
    $sort_flag  = sort_flag($product['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('product_info_new.htm'), '',
        array('filter' => $product['filter'], 'page_count' => $product['page_count']));
}

/*------------------------------------------------------ */
//-- 货品删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'product_remove')
{
    /* 检查权限 */
    check_authz_json('remove_back');

    /* 是否存在商品id */
    if (empty($_REQUEST['id']))
    {
        make_json_error($_LANG['product_id_null']);
    }
    else
    {
        $product_id = intval($_REQUEST['id']);
    }

    /* 货品库存 */
    $product = get_product_info($product_id, 'product_number, goods_id');

    /* 删除货品 */
    $sql = "DELETE FROM " . $ecs->table('products') . " WHERE product_id = '$product_id'";
    $result = $db->query($sql);
    if ($result)
    {
        /* 修改商品库存 */
        $product_count = product_number_count($product['goods_id']);
        update_goods($product['goods_id'], 'goods_number', $product_count);
        admin_log('', 'update', 'goods');

        //记录日志
        admin_log('', 'trash', 'products');

        $url = 'goods.php?act=product_query&' . str_replace('act=product_remove', '', $_SERVER['QUERY_STRING']);

        ecs_header("Location: $url\n");
        exit;
    }
}

/*------------------------------------------------------ */
//-- 修改货品价格
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_product_sn')
{
    check_authz_json('goods_manage');

    $product_id       = intval($_POST['id']);
    $product_sn       = json_str_iconv(trim($_POST['val']));
    $product_sn       = ($_LANG['n_a'] == $product_sn) ? '' : $product_sn;

    if (check_product_sn_exist($product_sn, $product_id))
    {
        make_json_error($_LANG['sys']['wrong'] . $_LANG['exist_same_product_sn']);
    }

    /* 修改 */
    $sql = "UPDATE " . $ecs->table('products') . " SET product_sn = '$product_sn' WHERE product_id = '$product_id'";
    $result = $db->query($sql);
    if ($result)
    {
        clear_cache_files();
        make_json_result($product_sn);
    }
}

/*------------------------------------------------------ */
//-- 修改货品库存
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_product_number')
{
    check_authz_json('goods_manage');

    $product_id       = intval($_POST['id']);
    $product_number       = intval($_POST['val']);

    /* 货品库存 */
    $product = get_product_info($product_id, 'product_number, goods_id');

    /* 修改货品库存 */
    $sql = "UPDATE " . $ecs->table('products') . " SET product_number = '$product_number' WHERE product_id = '$product_id'";
    $result = $db->query($sql);
    if ($result)
    {
        /* 修改商品库存 */
        $product_count = product_number_count($product['goods_id']);
        update_goods($product['goods_id'], 'goods_number', $product_count);

        clear_cache_files();
        make_json_result($product_number);
    }
}

/*------------------------------------------------------ */
//-- 货品添加 执行
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'product_add_execute')
{
    admin_priv('goods_manage');
    $product['goods_id']        = intval($_POST['goods_id']);
    $product['attr']            = $_POST['attr'];
    $product['product_sn']      = $_POST['product_sn'];
    $product['product_number']  = $_POST['product_number'];

    /* 是否存在商品id */
    if (empty($product['goods_id']))
    {
        sys_msg($_LANG['sys']['wrong'] . $_LANG['cannot_found_goods'], 1, array(), false);
    }

    /* 判断是否为初次添加 */
    $insert = true;
    if (product_number_count($product['goods_id']) > 0)
    {
        $insert = false;
    }

    /* 取出商品信息 */
    $sql = "SELECT goods_sn, goods_name, goods_type, shop_price FROM " . $ecs->table('goods') . " WHERE goods_id = '" . $product['goods_id'] . "'";
    $goods = $db->getRow($sql);
    if (empty($goods))
    {
        sys_msg($_LANG['sys']['wrong'] . $_LANG['cannot_found_goods'], 1, array(), false);
    }

    /*  */
    foreach($product['product_sn'] as $key => $value)
    {
        //过滤
//      $product['product_number'][$key] = empty($product['product_number'][$key]) ? (empty($_CFG['use_storage']) ? 0 : $_CFG['default_storage']) : trim($product['product_number'][$key]); //库存
        $product['product_number'][$key] = trim($product['product_number'][$key]); //库存

        //获取规格在商品属性表中的id
        foreach($product['attr'] as $attr_key => $attr_value)
        {
            /* 检测：如果当前所添加的货品规格存在空值或0 */
            if (empty($attr_value[$key]))
            {
                continue 2;
            }

            $is_spec_list[$attr_key] = 'true';
            $value_price_list[$attr_key] = $attr_value[$key] . chr(9) . ''; //$key，当前

            $id_list[$attr_key] = $attr_key;
        }

        $goods_attr_id = handle_goods_attr($product['goods_id'], $id_list, $is_spec_list, $value_price_list);
        /* 是否为重复规格的货品 */
        $goods_attr = sort_goods_attr_id_array($goods_attr_id);
        $goods_attr = implode('|', $goods_attr['sort']);
        if (check_goods_attr_exist($goods_attr, $product['goods_id']))
        {
            continue;
            //sys_msg($_LANG['sys']['wrong'] . $_LANG['exist_same_goods_attr'], 1, array(), false);
        }
        //货品号不为空
        if (!empty($value))
        {
            /* 检测：货品货号是否在商品表和货品表中重复 */
            if (check_goods_sn_exist($value))
            {
                continue;
                //sys_msg($_LANG['sys']['wrong'] . $_LANG['exist_same_goods_sn'], 1, array(), false);
            }
            if (check_product_sn_exist($value))
            {
                continue;
                //sys_msg($_LANG['sys']['wrong'] . $_LANG['exist_same_product_sn'], 1, array(), false);
            }
        }

        /* 插入货品表 */
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('products') . " (goods_id, goods_attr, product_sn, product_number)  VALUES ('" . $product['goods_id'] . "', '$goods_attr', '$value', '" . $product['product_number'][$key] . "')";

        if (!$GLOBALS['db']->query($sql))
        {
            continue;
            //sys_msg($_LANG['sys']['wrong'] . $_LANG['cannot_add_products'], 1, array(), false);
        }
        //货品号为空 自动补货品号
        if (empty($value))
        {
            $sql = "UPDATE " . $GLOBALS['ecs']->table('products') . "
                    SET product_sn = '" . $goods['goods_sn'] . "g_p" . $GLOBALS['db']->insert_id() . "'
                    WHERE product_id = '" . $GLOBALS['db']->insert_id() . "'";
            $GLOBALS['db']->query($sql);
        }

    }

    /* 修改商品表库存 */
    $product_count = product_number_count($product['goods_id']);
    if (update_goods($product['goods_id'], 'goods_number', $product_count))
    {
        //记录日志
        admin_log($product['goods_id'], 'update', 'goods');
    }


    $ids_arr = isset($_POST['product_id']) ? $_POST['product_id'] : '';
    if(!empty($ids_arr)){
        foreach ($ids_arr as $k => $v){
            $new_goods_attr = isset($_POST["new_attr_$v"]) ? $_POST["new_attr_$v"] : '';
            $flag = true;
            if(!empty($new_goods_attr)){
                foreach ($new_goods_attr as $key => $val){
                    if(empty($val)){
                       $flag = true;
                       break;
                    }else{
                        $flag = false;
                    }
                }
            }
            if($flag){
                continue;
            }
            $sql = "SELECT goods_attr FROM " . $GLOBALS['ecs']->table('products') . " WHERE product_id = '$v'";
            $goods_attr = $GLOBALS['db']->getOne($sql);
            $goods_attr = explode('|',$goods_attr);
            $new_attr = array_merge($new_goods_attr,$goods_attr);
            sort($new_attr);
            $sql = "SELECT attr_id FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id ".db_create_in($new_attr);
            $attr_id_data = $GLOBALS['db']->query($sql);
            $key_data = [];
            while ($rows =  $GLOBALS['db']->fetchRow($attr_id_data)){
                $key_data[] = $rows['attr_id'];
            }
            $new_attr = array_combine($key_data,$new_attr);
            ksort($new_attr);
            $attr_str = implode('|',$new_attr);
            $sql = "UPDATE " . $GLOBALS['ecs']->table('products') . "
                SET goods_attr = '" .$attr_str . "'
                WHERE product_id = '" . $v . "'";
            $GLOBALS['db']->query($sql);
        }
    }

    clear_cache_files();

    /* 返回 */
    if ($insert)
    {
         $link[] = array('href' => 'goods.php?act=add', 'text' => $_LANG['02_goods_add']);
         $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
         $link[] = array('href' => 'goods.php?act=product_list&goods_id=' . $product['goods_id'], 'text' => $_LANG['18_product_list']);
    }
    else
    {
         $link[] = array('href' => 'goods.php?act=list&uselastfilter=1', 'text' => $_LANG['01_goods_list']);
         $link[] = array('href' => 'goods.php?act=edit&goods_id=' . $product['goods_id'], 'text' => $_LANG['edit_goods']);
         $link[] = array('href' => 'goods.php?act=product_list&goods_id=' . $product['goods_id'], 'text' => $_LANG['18_product_list']);
    }
    sys_msg($_LANG['save_products'], 0, $link);
}

/*------------------------------------------------------ */
//-- 货品批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_product')
{
    /* 定义返回 */
    $link[] = array('href' => 'goods.php?act=product_list&goods_id=' . $_POST['goods_id'], 'text' => $_LANG['item_list']);

    /* 批量操作 - 批量删除 */
    if ($_POST['type'] == 'drop')
    {
        //检查权限
        admin_priv('remove_back');

        //取得要操作的商品编号
        $product_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
        $product_bound = db_create_in($product_id);

        //取出货品库存总数
        $sum = 0;
        $goods_id = 0;
        $sql = "SELECT product_id, goods_id, product_number FROM  " . $GLOBALS['ecs']->table('products') . " WHERE product_id $product_bound";
        $product_array = $GLOBALS['db']->getAll($sql);
        if (!empty($product_array))
        {
            foreach ($product_array as $value)
            {
                $sum += $value['product_number'];
            }
            $goods_id = $product_array[0]['goods_id'];

            /* 删除货品 */
            $sql = "DELETE FROM " . $ecs->table('products') . " WHERE product_id $product_bound";
            if ($db->query($sql))
            {
                //记录日志
                admin_log('', 'delete', 'products');
            }

            /* 修改商品库存 */
            $product_count = product_number_count($goods_id);
            update_goods($goods_id, 'goods_number', $product_count);
            admin_log('', 'update', 'goods');

            /* 返回 */
            sys_msg($_LANG['product_batch_del_success'], 0, $link);
        }
        else
        {
            /* 错误 */
            sys_msg($_LANG['cannot_found_products'], 1, $link);
        }
    }

    /* 返回 */
    sys_msg($_LANG['no_operation'], 1, $link);
}
/*------------------------------------------------------ */
//-- 修改商品虚拟数量
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_virtual_sales')
{
    check_authz_json('goods_manage');

    $goods_id   = intval($_POST['id']);
    $virtual_sales  = intval($_POST['val']);

    if($virtual_sales < 0 || $virtual_sales == 0 && $_POST['val'] != "$virtual_sales")
    {
        make_json_error($_LANG['virtual_sales_error']);
    }

    if(check_goods_product_exist($goods_id) == 1)
    {
        make_json_error($_LANG['sys']['wrong'] . $_LANG['cannot_goods_number']);
    }

    if ($exc->edit("virtual_sales = '$virtual_sales', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result($virtual_sales);
    }
}

elseif ($_REQUEST['act'] == 'get_category')
{
    $type   = !empty($_REQUEST['type'])   ? intval($_REQUEST['type'])   : 0;
    $parent = !empty($_REQUEST['parent']) ? intval($_REQUEST['parent']) : 0;

    $arr['regions'] = category_list($parent);
    $arr['type']    = $type;
    $arr['target']  = !empty($_REQUEST['target']) ? stripslashes(trim($_REQUEST['target'])) : '';
    $arr['target']  = htmlspecialchars($arr['target']);

    echo json_encode($arr);
}

/*------------------------------------------------------ */
//-- 库存调整
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'set_stock')
{
    admin_priv('goods_manage'); // 检查权限

    /* 是否存在商品id */
    if (empty($_GET['goods_id']))
    {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['cannot_found_goods']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    }
    else
    {
        $g_id = intval($_GET['goods_id']);
    }

    /* 取出商品信息 */
    $sql = "SELECT goods_sn, goods_name, goods_number FROM " . $ecs->table('goods') . " WHERE goods_id = '$g_id'";
    $goods = $db->getRow($sql);
    if (empty($goods))
    {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    }
    $smarty->assign('goods_name', sprintf($_LANG['products_title'], $goods['goods_name']));
    $smarty->assign('goods_sn', sprintf($_LANG['products_title_2'], $goods['goods_sn']));
    $smarty->assign('goods_number', $goods['goods_number']);

    /* 是否有规格 */
    $sql = "SELECT goods_id  FROM " . $ecs->table('products') . " WHERE goods_id = '$g_id'";
    $goods_id = $db->getOne($sql);
    $is_product = !empty($goods_id) ? true : false;
    $smarty->assign('is_product',     $is_product);
    if($is_product){
        /* 获取商品规格列表 */
        $attribute = get_goods_specifications_list($goods_id);

        if (empty($attribute))
        {
            $link[] = array('href' => 'goods.php?act=edit&goods_id=' . $goods_id, 'text' => $_LANG['edit_goods']);
            sys_msg($_LANG['not_exist_goods_attr'], 1, $link);
        }
        foreach ($attribute as $attribute_value)
        {
            //转换成数组
            $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
            $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
            $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            $new_attr[$attribute_value['attr_id']][$attribute_value['goods_attr_id']] = $attribute_value['attr_value'];
        }

        /* 取商品的货品 */
        $product = product_list($goods_id, '');
        foreach ($product['product'] as $k => $v){
            foreach ($new_attr as $key => $val){
                $diff = array_diff($val,$v['goods_attr']);
                if(count($diff) == count($val)){
                    $product['product'][$k]['goods_attr'][] = '-';
                }
            }
        }
        $smarty->assign('attribute',                $_attribute);
        $smarty->assign('product_list', $product['product']);
    }
    $smarty->assign('goods_id',     $g_id);
    $smarty->assign('ur_here',      $_LANG['goods_set_stock']);
    /* 显示商品列表页面 */
    assign_query_info();

    $smarty->display('goods_stock.htm');

}

elseif ($_REQUEST['act'] == 'set_settlement_money')
{
    admin_priv('goods_manage'); // 检查权限

    /* 是否存在商品id */
    if (empty($_GET['goods_id']))
    {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['cannot_found_goods']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    }
    else
    {
        $g_id = intval($_GET['goods_id']);
    }

    /* 取出商品信息 */
    $sql = "SELECT goods_sn, goods_name, goods_number,settlement_money,suppliers_id FROM " . $ecs->table('goods') . " WHERE goods_id = '$g_id'";
    $goods = $db->getRow($sql);
    if (empty($goods))
    {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    }

    $sql = "SELECT cooperate_type FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = '".$goods['suppliers_id']."'";
    $suppliers = $db->getRow($sql);

    if (2 != $suppliers['cooperate_type']) {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
        sys_msg("只有入驻商才能有供货价", 1, $link);
    }

    $smarty->assign('goods_name', sprintf($_LANG['products_title'], $goods['goods_name']));
    $smarty->assign('goods_sn', sprintf($_LANG['products_title_2'], $goods['goods_sn']));
    $smarty->assign('goods_number', $goods['goods_number']);
    $smarty->assign('goods', $goods);

    $smarty->assign('goods_id',     $g_id);
    $smarty->assign('ur_here',      $_LANG['goods_set_stock']);
    /* 显示商品列表页面 */
    assign_query_info();

    $smarty->display('goods_settlement_money.htm');

}

/*------------------------------------------------------ */
//-- 增加减少库存
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update_stock')
{
    admin_priv('goods_manage'); // 检查权限
    /* 是否存在商品id */
    if (empty($_POST['goods_id'])) {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['cannot_found_goods']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    } else {
        $goods_id = intval($_POST['goods_id']);
    }
    $db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
    $db->query('START TRANSACTION');//开启事务
    $sql = "select * from " . $GLOBALS['ecs']->table('goods') . " where goods_id=" . $goods_id . " for update ";
    $goods = $GLOBALS['db']->getRow($sql);
    if (empty($goods)) {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    }
    if ($_POST['type']) {
        $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
        $product_type = isset($_POST['product_type']) ? $_POST['product_type'] : '';
        $product_num = isset($_POST['product_num']) ? $_POST['product_num'] : '';
        foreach ($product_id as $key => $val) {
            /* 货品库存 */
            $sql = "SELECT product_number FROM  " . $GLOBALS['ecs']->table('products') . " WHERE product_id = '$val'";
            $number = $db->getOne($sql);
            if ($product_type[$key] == '') {
                $db->query("ROLLBACK"); //事务回滚
                sys_msg("修改失败");
            }
            if ($product_type[$key]) {
                $product_number = intval($number) + intval($product_num[$key]);
            } else {
                $flg = intval($number) - intval($product_num[$key]) >= 0 ? true : false;
                if (!$flg) {
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg("库存变更后，货品库存会变为负数，请重新设置");
                }
                $product_number = intval($number) - intval($product_num[$key]);
            }

            /* 修改货品库存 */
            $sql = "UPDATE " . $ecs->table('products') . " SET product_number = '$product_number' WHERE product_id = '$val'";
            $result = $db->query($sql);
            if ($result) {
                /* 修改商品库存 */
                $product_count = product_number_count($goods_id);
                if (!update_goods($goods_id, 'goods_number', $product_count)) {
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg("修改失败");
                }
            } else {
                $db->query("ROLLBACK"); //事务回滚
                sys_msg("修改失败");
            }
        }
    } else {
        $goods_type = isset($_POST['goods_type']) ? $_POST['goods_type'] : '';
        $goods_num = isset($_POST['goods_num']) ? $_POST['goods_num'] : 0;
        if ($goods_type == '') {
            $db->query("ROLLBACK"); //事务回滚
            sys_msg("修改失败");
        }
        $number = $goods['goods_number'];
        if ($goods_type) {
            $goods_number = intval($number) + intval($goods_num);
        } else {
            $flg = intval($number) - intval($goods_num) >= 0 ? true : false;
            if (!$flg) {
                $db->query("ROLLBACK"); //事务回滚
                sys_msg("库存变更后，货品库存会变为负数，请重新设置");
            }
            $goods_number = intval($number) - intval($goods_num);
        }
        /* 修改商品库存 */
        $res = update_goods_stock($goods_id, $goods_number - $number);
        if (!$res) {
            $db->query("ROLLBACK"); //事务回滚
            sys_msg("修改失败");
        }
    }

    $db->query("COMMIT"); //事务提交

    /* 提示页面 */
    $link = array();

    $link[0] = array('href' => 'goods.php?act=set_stock&goods_id=' . $goods_id, 'text' => $_LANG['goods_set_stock']);
    $link[1] = array('href' => 'goods.php?act=list&uselastfilter=1', 'text' => $_LANG['back_goods_list']);
    sys_msg($_LANG['edit_stock_ok'], 0, $link);

}

elseif ($_REQUEST['act'] == 'update_settlement_money')
{

    admin_priv('goods_manage'); // 检查权限
    /* 是否存在商品id */
    if (empty($_POST['goods_id'])) {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['cannot_found_goods']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    } else {
        $goods_id = intval($_POST['goods_id']);
    }
    $db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
    $db->query('START TRANSACTION');//开启事务
    $sql = "select * from " . $GLOBALS['ecs']->table('goods') . " where goods_id=" . $goods_id . " for update ";
    $goods = $GLOBALS['db']->getRow($sql);
    if (empty($goods)) {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
        sys_msg($_LANG['cannot_found_goods'], 1, $link);
    }


    $sql = "SELECT cooperate_type FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = '".$goods['suppliers_id']."'";
    $suppliers = $db->getRow($sql);

    if (2 != $suppliers['cooperate_type']) {
        $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
        sys_msg("只有入驻商才能有供货价", 1, $link);
    }

    if (!empty($_POST['settlement_money']) && 0.00 != $_POST['settlement_money']) {
        $settlement_money   = $_POST['settlement_money'];

        /* 修改货品库存 */
        $sql = "UPDATE " . $ecs->table('goods') . " SET settlement_money = '$settlement_money' WHERE goods_id = '$goods_id'";
        $result = $db->query($sql);

    }

    $db->query("COMMIT"); //事务提交

    /* 提示页面 */
    $link = array();

    $link[0] = array('href' => 'goods.php?act=set_settlement_money&goods_id=' . $goods_id, 'text' => $_LANG['goods_set_settlement_money']);
    $link[1] = array('href' => 'goods.php?act=list&uselastfilter=1', 'text' => $_LANG['back_goods_list']);
    sys_msg($_LANG['edit_settlement_money_ok'], 0, $link);

}

/*------------------------------------------------------ */
//-- 撤销审核
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'cancle_audit')
{
    $goods_id = $_REQUEST['goods_id'] ? intval($_REQUEST['goods_id']) : 0;
    $user_id = $_SESSION['admin_id'];
    $time = gmtime();
    $sql = "SELECT status FROM  " . $GLOBALS['ecs']->table('goods_action') . " WHERE goods_id = '$goods_id' ORDER BY add_time DESC limit 1,1";
    $status = $db->getOne($sql);
    switch ($status)
    {
        case 5:
           $status = 2;
             break;
         default:
            $status = 0;
    }
    if ($exc->edit("is_check = ".$status.", last_update=" . gmtime(), $goods_id)) {
        clear_cache_files();
        $goods_name = $exc->get_name($goods_id);
        goods_action_log($goods_id,$status,'撤销审核');
        admin_log(addslashes($goods_name), 'cancle_audit', 'goods');
        $url = 'goods.php?act=list&uselastfilter=1';
        ecs_header("Location: $url\n");
    }
}


/**
 * 列表链接
 * @param   bool    $is_add         是否添加（插入）
 * @param   string  $extension_code 虚拟商品扩展代码，实体商品为空
 * @return  array('href' => $href, 'text' => $text)
 */
function list_link($is_add = true, $extension_code = '')
{
    $href = 'goods.php?act=list';
    if (!empty($extension_code))
    {
        $href .= '&extension_code=' . $extension_code;
    }
    if (!$is_add)
    {
        $href .= '&' . list_link_postfix();
    }
    if(isset($_REQUEST['status'])){
        $href .= "&status={$_REQUEST['status']}";
    }

    if ($extension_code == 'virtual_card')
    {
        $text = $GLOBALS['_LANG']['50_virtual_card_list'];
    }
    else
    {
        $text = $GLOBALS['_LANG']['01_goods_list'];
    }

    return array('href' => $href, 'text' => $text);
}

/**
 * 添加链接
 * @param   string  $extension_code 虚拟商品扩展代码，实体商品为空
 * @return  array('href' => $href, 'text' => $text)
 */
function add_link($extension_code = '')
{
    $href = 'goods.php?act=add';
    if (!empty($extension_code))
    {
        $href .= '&extension_code=' . $extension_code;
    }
    if(isset($_REQUEST['status'])){
        $href .= "&status={$_REQUEST['status']}";
    }

    if ($extension_code == 'virtual_card')
    {
        $text = $GLOBALS['_LANG']['51_virtual_card_add'];
    }
    else
    {
        $text = $GLOBALS['_LANG']['02_goods_add'];
    }

    return array('href' => $href, 'text' => $text);
}

/**
 * 检查图片网址是否合法
 *
 * @param string $url 网址
 *
 * @return boolean
 */
function goods_parse_url($url)
{
    $parse_url = @parse_url($url);
    return (!empty($parse_url['scheme']) && !empty($parse_url['host']));
}

/**
 * 保存某商品的优惠价格
 * @param   int     $goods_id    商品编号
 * @param   array   $number_list 优惠数量列表
 * @param   array   $price_list  价格列表
 * @return  void
 */
function handle_volume_price($goods_id, $number_list, $price_list)
{
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('volume_price') .
           " WHERE price_type = '1' AND goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);


    /* 循环处理每个优惠价格 */
    foreach ($price_list AS $key => $price)
    {
        /* 价格对应的数量上下限 */
        $volume_number = $number_list[$key];

        if (!empty($price))
        {
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('volume_price') .
                   " (price_type, goods_id, volume_number, volume_price) " .
                   "VALUES ('1', '$goods_id', '$volume_number', '$price')";
            $GLOBALS['db']->query($sql);
        }
    }
}

/**
 * 修改商品库存
 * @param   string  $goods_id   商品编号，可以为多个，用 ',' 隔开
 * @param   string  $value      字段值
 * @return  bool
 */
function update_goods_stock($goods_id, $value)
{
    if ($goods_id)
    {
        /* $res = $goods_number - $old_product_number + $product_number; */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . "
                SET goods_number = goods_number + $value,
                    last_update = '". gmtime() ."'
                WHERE goods_id = '$goods_id'";
        $result = $GLOBALS['db']->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        return $result;
    }
    else
    {
        return false;
    }
}

/**
 * 编辑换换客商品，记录修改了哪些字段  老版本hhk已去除
 */
/*function mlm_goods_edit_check($shop_price, $money_line, $rebate, $is_on_sale, $data, $goods_name)
{
    $flg = false;
    $content = '编辑换换客商品：' . $goods_name;
    foreach ($data as $v) {
        if (floatval($shop_price) != floatval($v['shop_price'])) {
            $content .= '，原商品售价：' . $v['shop_price'] . '-' . $shop_price;
            $flg = true;
        }
        if (floatval($money_line) != floatval($v['money_line'])) {
            $content .= '，原权益币金额：' . $v['money_line'] . '-' . $money_line;
            $flg = true;
        }
        if (floatval($rebate) != floatval($v['rebate'])) {
            $content .= '，原返佣金额：' . $v['rebate'] . '-' . $rebate;
            $flg = true;
        }
        if (intval($is_on_sale) != intval($v['is_on_sale'])) {
            $old = $v['is_on_sale'] == 1 ? '上架' : '下架';
            $new = $is_on_sale == 1 ? '上架' : '下架';
            $content .= "，原是否销售：" .$old.'-'.$new;
            $flg = true;
        }
        if($flg){
            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_mlm') . " SET " .
                        "shop_price = '$shop_price', " .
                        "money_line = '$money_line', " .
                        "rebate = '$rebate', " .
                        "is_on_sale = '$is_on_sale' ," .
                        "updated_at = '".gmtime()."' " .
                        "WHERE goods_id = ".$v['goods_id']." LIMIT 1";
            $GLOBALS['db']->query($sql);
            $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('goods_action') . ' (goods_id, status, user_id, remark, add_time) ' .
                " VALUES ('{$v['goods_id']}', 7, '{$_SESSION['admin_id']}', '{$content}'," . time() . ")";
            $GLOBALS['db']->query($sql);
            $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('admin_log') . ' (log_time, user_id, log_info, ip_address) ' .
                " VALUES ('" . gmtime() . "', $_SESSION[admin_id], '" . '编辑换换客商品：' . $goods_name . "', '" . real_ip() . "')";
            $GLOBALS['db']->query($sql);
        }
    }
}*/


function category_list($parent_id = 0, $is_show = false){
    $sql = "select cat_id, cat_name from ".$GLOBALS['ecs']->table('category')." where parent_id = {$parent_id}" . (!$is_show ? "" : " and is_show = 1") . " order by parent_id,sort_order,cat_id";
    return $GLOBALS['db']->getAll($sql);
}


function saveCustomSpecification($goods_id,$list){
    //开启事务
    $GLOBALS['db']->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
    $GLOBALS['db']->query('START TRANSACTION');
    $sql = " delete ".$GLOBALS['ecs']->table('attribute').",".$GLOBALS['ecs']->table('goods_attr')." from  ".$GLOBALS['ecs']->table('attribute').",".$GLOBALS['ecs']->table('goods_attr')."  where " . $GLOBALS['ecs']->table('attribute') . ".attr_id = " . $GLOBALS['ecs']->table('goods_attr') . ".attr_id  and " . $GLOBALS['ecs']->table('attribute') . ".cat_id = 0 and  " . $GLOBALS['ecs']->table('attribute') . ".category_id = 0 and " . $GLOBALS['ecs']->table('attribute') . ".attr_type = 0 and  " . $GLOBALS['ecs']->table('goods_attr') . ".goods_id = ".$goods_id;
    if($GLOBALS['db']->query($sql)===false){
        $GLOBALS['db']->query("ROLLBACK"); //事务回滚
        return false;
    }
    foreach ($list as $name => $value) {
        $insertAttrSql = " insert into ".$GLOBALS['ecs']->table('attribute')." (attr_name,attr_input_type,attr_type,attr_values) values ('$name',0,0,'') ";
        if($GLOBALS['db']->query($insertAttrSql) === false){
            $GLOBALS['db']->query("ROLLBACK"); //事务回滚
            return false;
        }
        $attr_id = $GLOBALS['db']->insert_id();

        $insertGoodsAttrSql = " insert into ".$GLOBALS['ecs']->table('goods_attr')." (goods_id,attr_id,attr_value,attr_image) values ($goods_id,$attr_id,'$value','') ";

        if($GLOBALS['db']->query($insertGoodsAttrSql)===false){
            $GLOBALS['db']->query("ROLLBACK"); //事务回滚
            return false;
        }
    }
    $GLOBALS['db']->query("COMMIT"); //提交事务
    return true;
}

function getCustomSpecification($goods_id=0){
    $sql = " select a.attr_name, ga.attr_value from ".$GLOBALS['ecs']->table('attribute')." as a ,".$GLOBALS['ecs']->table('goods_attr')." as ga where a.attr_id = ga.attr_id and ga.goods_id = $goods_id and a.cat_id = 0 and  a.category_id = 0 and a.attr_type = 0 order by a.attr_id  ";
    return $GLOBALS['db']->getAll($sql);
}

?>
