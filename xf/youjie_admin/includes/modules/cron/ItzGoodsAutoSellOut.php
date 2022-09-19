<?php

/**
 * 售罄自动下架
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/ItzGoodsAutoSellOut.php';
if (file_exists($cron_lang))
{
    global $_LANG;

    include_once($cron_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{

    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'ItzGoodsAutoSellOut_desc';

    /* 作者 */
    $modules[$i]['author']  = 'admin';

    /* 网址 */
    $modules[$i]['website'] = '';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array();

    /* 执行时间 */
    $modules[$i]['week']    = -1;
    $modules[$i]['day']     = 0;
    $modules[$i]['minute']  = '0';

    return;
}

$goods = $db->getAll('SELECT goods_id FROM ' . $ecs->table('goods') . ' WHERE is_on_sale=1 AND goods_number=0 AND is_sell_out=1');
foreach($goods as $good) {
    $goods_id = $good['goods_id'];
    // 当待付款订单为0时才可以下架商品
    $total = $db->getOne('SELECT COUNT(*) AS total FROM '.$ecs->table('order_goods').' a LEFT JOIN '.$ecs->table('order_info').' b ON a.order_id=b.order_id WHERE order_status<=1 AND pay_status=0 AND goods_id='.$goods_id);
    if(!$total) {
        $db->query('UPDATE '.$ecs->table('goods').' SET is_on_sale=0 WHERE goods_id='.$goods_id);
        file_put_contents(ROOT_PATH.'temp/common/common.log', implode("\t", array(
            time(),
            $_LANG['ItzGoodsAutoSellOut'],
            'goods_id='.$goods_id
        )), FILE_APPEND);
    }
}

?>
