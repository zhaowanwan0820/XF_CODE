<?php

/**
 * ECSHOP 销售明细列表程序
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: sale_list.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/statistic.php');
$smarty->assign('lang', $_LANG);

if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' ||  $_REQUEST['act'] == 'download'))
{

    /* 检查权限 */
    check_authz_json('sale_order_stats');
    if (strstr($_REQUEST['start_date'], '-') === false)
    {
        $_REQUEST['start_date'] = strtotime('Y-m-d', $_REQUEST['start_date']);
        $_REQUEST['end_date'] = strtotime('Y-m-d', $_REQUEST['end_date']);
    }
    /*------------------------------------------------------ */
    //--Excel文件下载
    /*------------------------------------------------------ */
    if ($_REQUEST['act'] == 'download')
    {
        $file_name = $_REQUEST['start_date'].'_'.$_REQUEST['end_date'] . '_sale';
        $goods_sales_list = get_sale_list(false);

        header("Content-type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$file_name.xls");

        /* 文件标题 */
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_REQUEST['start_date']. $_LANG['to'] .$_REQUEST['end_date']. $_LANG['sales_list']) . "\t\n";

        /* 商品名称,订单号,商品数量,销售价格,销售日期 */
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['goods_sn']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['goods_name']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['order_sn']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['sell_date']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['shop_price']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['amount']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['provider_name']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['consignee']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['province']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['city']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['district']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['address']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['zipcode']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['mobile']) . "\t\n";

        foreach ($goods_sales_list['sale_list_data'] AS $key => $value)
        {
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['goods_sn']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['goods_name']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', " ".$value['order_sn']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['time']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['shop_price']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['goods_number']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['provider_name']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['consignee']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['province']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['city']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['district']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['address']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['zipcode']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['mobile']). "\t";
            echo "\n";
        }
        exit;
    }
    $sale_list_data = get_sale_list();
    $smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
    $smarty->assign('filter',       $sale_list_data['filter']);
    $smarty->assign('record_count', $sale_list_data['record_count']);
    $smarty->assign('page_count',   $sale_list_data['page_count']);

    make_json_result($smarty->fetch('sale_list.htm'), '', array('filter' => $sale_list_data['filter'], 'page_count' => $sale_list_data['page_count']));
}
/*------------------------------------------------------ */
//--商品明细列表
/*------------------------------------------------------ */
else
{

    /* 权限判断 */
    admin_priv('sale_order_stats');
    /* 时间参数 */
    if (!isset($_REQUEST['start_date']))
    {
        $start_date = strtotime('-7 days');
    }
    if (!isset($_REQUEST['end_date']))
    {
        $end_date = strtotime('today');
    }

    $sale_list_data = get_sale_list();
    /* 赋值到模板 */
    $smarty->assign('filter',       $sale_list_data['filter']);
    $smarty->assign('record_count', $sale_list_data['record_count']);
    $smarty->assign('page_count',   $sale_list_data['page_count']);
    $smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
    $smarty->assign('ur_here',          $_LANG['sell_stats']);
    $smarty->assign('full_page',        1);
//    $smarty->assign('start_date',       local_date('Y-m-d', $start_date));
//    $smarty->assign('end_date',         local_date('Y-m-d', $end_date));
    $smarty->assign('ur_here',      $_LANG['sale_list']);
    $smarty->assign('cfg_lang',     $_CFG['lang']);
    $smarty->assign('action_link',  array('text' => $_LANG['down_sales'],'href'=>'#download'));

    /* 显示页面 */
    assign_query_info();
    $smarty->display('sale_list.htm');
}
/*------------------------------------------------------ */
//--获取销售明细需要的函数
/*------------------------------------------------------ */
/**
 * 取得销售明细数据信息
 * @param   bool  $is_pagination  是否分页
 * @return  array   销售明细数据
 */
function get_sale_list($is_pagination = true){
    /* 时间参数 */
    $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
    $filter['start_date'] = empty($_REQUEST['start_date']) ? $beginThismonth : strtotime($_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? time() : strtotime($_REQUEST['end_date']);
    /* 查询数据的条件 */
    $where = "WHERE a.pay_status = 2 AND a.add_time BETWEEN " .$filter['start_date']." and ".($filter['end_date'] + 86400);
    $sql = "select
            count(1)
            FROM
            " . $GLOBALS['ecs']->table('order_info') . "  AS  a
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  b  ON  a.country  =  b.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  c  ON  a.province  =  c.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  d  ON  a.city  =  d.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  e  ON  a.district  =  e.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('order_goods') . "  g  ON  a.order_id  =  g.order_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('goods') . "  f  ON  g.goods_id  =  f.goods_id ".$where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    /* 分页大小 */
    $filter = page_and_size($filter);
    $sql = "select
            g.goods_sn,
            f.goods_name,
            a.order_sn,
            FROM_UNIXTIME(
            a.add_time
            )  AS  time,
            f.shop_price,
            g.goods_number,
            a.consignee,
            f.provider_name,
            IFNULL(c.region_name,  '') as province,
            IFNULL(d.region_name,  '') as city,
            IFNULL(e.region_name,  '') as district,
            a.address,
            a.zipcode,
            a.mobile
            FROM
            " . $GLOBALS['ecs']->table('order_info') . "  AS  a
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  b  ON  a.country  =  b.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  c  ON  a.province  =  c.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  d  ON  a.city  =  d.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  e  ON  a.district  =  e.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('order_goods') . "  g  ON  a.order_id  =  g.order_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('goods') . "  f  ON  g.goods_id  =  f.goods_id " .$where;
    if ($is_pagination)
    {
        $sql .= " ORDER BY a.add_time asc "." LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }
    $sale_list_data = $GLOBALS['db']->getAll($sql);
    foreach ($sale_list_data as $key => $item)
    {
        $sale_list_data[$key]['sales_price'] = price_format($sale_list_data[$key]['sales_price']);
        $sale_list_data[$key]['sales_time']  = $item['time'];
    }
    $arr = array('sale_list_data' => $sale_list_data, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}
?>