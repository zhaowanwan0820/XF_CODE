<?php

/**
 * 自动取消已付款订单
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/order_cancle.php';
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
    $modules[$i]['desc']    = 'order_cancle_desc';

    /* 作者 */
    $modules[$i]['author']  = 'admin';

    /* 网址 */
    $modules[$i]['website'] = '';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.1';

    /* 配置信息 */
    $modules[$i]['config']  = array();
    return;
}




/*
 * 自動取消已付款訂單
 * */

$orderids = [
    2019051423960,
    2019051413446,
    2019051476470,
    2019051458716,
    2019051462654,
    2019051426504,
    2019051411596,
    2019051492425,
    2019051407054,
    2019051488694,
    2019051409552,
    2019051487038,
    2019051471944,
    2019051486658,
    2019051485471,
    2019051411797,
    2019051442139,
    2019051430306,
    2019051433517,
    2019051486725,
    2019051402044,
    2019051495113,
    2019051477632,
    2019051459506,
    2019051434721,
    2019051464538,
    2019051463331,
    2019051494160,
    2019051462213,
    2019051415682,
    2019051489249,
    2019051485649,
    2019051497699,
    2019051485762,
    2019051405755,
    2019051456865,
    2019051461791,
    2019051464396,
    2019051488947,
    2019051417938,
    2019051497947,
    2019051437485,
    2019051423095,
    2019051469672,
    2019051493501,
    2019051416766,
    2019051470955,
    2019051451416,
    2019051465879,
    2019051492786,
    2019051468229,
    2019051490560,
    2019051433803,
    2019051440295,
    2019051498573,
    2019051474959,
    2019051467227,
    2019051494982,
    2019051498421,
    2019051497871,
    2019051445192,
    2019051451185,
    2019051491073,
    2019051435112,
    2019051470838,
    2019051473205,
    2019051415776,
    2019051459207,
    2019051444418,
    2019051429155,
    2019051472017,
    2019051472250,
    2019051497099,
    2019051476592,
    2019051478235,
    2019051443864,
    2019051454471,
    2019051491307,
    2019051488797,
    2019051482481,

];
include_once(ROOT_PATH.'includes/lib_order.php');

$db=$GLOBALS['db'];
if (count($orderids) > 0) {
    foreach ($orderids as $key => $val) {
        file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'批量取消脚本开始' ."\t" .date('Y-m-d H:i:s', time()), FILE_APPEND);

        //订单i d
        $order_id = $db->getOne("SELECT order_id FROM " . $ecs->table('order_info') . " WHERE order_sn = '$val'");

        /* 查询订单信息 */
        $order = order_info($order_id);
        $pay_status = $order['pay_status'];
        //本次已确认，已付款，发货中可以取消
        if ($order['order_status'] == 1 && $order['pay_status'] == 2 && $order['shipping_status'] == 5) {
            $db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
            $db->query('START TRANSACTION');//开启事务
            /* 标记订单为“取消”，记录取消原因 */
            $cancel_note = '批量脚本取消';
            $arr = array(
                'order_status' => OS_CANCELED,
                'to_buyer' => $cancel_note,
                'pay_status' => PS_UNPAYED,
                'shipping_status' => SS_UNSHIPPED,
                "surplus_back" => $order['surplus'],
                "cash_back" => $order['money_paid'],
            );
            $update_order_res = update_order($order_id, $arr);

            if (!$update_order_res) {
                $db->query("ROLLBACK"); //事务回滚
                file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'更新失败' . $val ."\t" . date('Y-m-d H:i:s', time()), FILE_APPEND);
                sys_msg($val.'订单更新失败');
            }

            /**已结算订单需要从商家账户扣回退款**/

            if ($order['suppliers_account_log_id'] > 0) {
                $res = dealOrderToSuppliersSettlement($order['suppliers_id'], $order['order_id'], $order['surplus'], $order['money_paid'], '脚本取消');
                if (!$res) {
                    $db->query("ROLLBACK"); //事务回滚
                    file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'已结算订单扣除商家账户退款失败' . $val ."\t" . date('Y-m-d H:i:s', time()), FILE_APPEND);
                    sys_msg($val.'已结算订单扣除商家账户退款失败');
                }
            }


            /* todo 处理退款 */
            if ($order['money_paid'] > 0) {
                /*//现金退款
                $refund_type = 4;
                $refund_note = "脚本取消退款"; //退款説明
                $order_refund_res = order_refund($order, $refund_type, $refund_note);
                if (!$order_refund_res) {
                    file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'现金退款失败' . $val ."\t" . date('Y-m-d H:i:s', time()), FILE_APPEND);
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg($val.'订单现金退款异常');
                }*/

                //此次是纯权益币订单
                $db->query("ROLLBACK"); //事务回滚
                sys_msg($val.'订单支付类型有误');
            }

            /* 记录log */
            $order_action_res = order_action($order['order_sn'], OS_CANCELED, SS_UNSHIPPED, PS_UNPAYED, $cancel_note);
            if (!$order_action_res) {
                $db->query("ROLLBACK"); //事务回滚
                file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'order_action记录失败' . $val ."\t" . date('Y-m-d H:i:s', time()), FILE_APPEND);
                sys_msg($val.'订单order_action记录失败');
            }

            /* 如果使用库存，且下订单时减库存，则增加库存 */
            if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE) {
                $change_order_goods_storage_res = change_order_goods_storage($order_id, false, SDT_PLACE);
                if (!$change_order_goods_storage_res) {
                    $db->query("ROLLBACK"); //事务回滚
                    file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'增加库存失败' . $val ."\t" . date('Y-m-d H:i:s', time()), FILE_APPEND);
                    sys_msg($val.'增加库存失败');
                }
                if($pay_status == PS_PAYED ){
                    $goods = $db->getRow("SELECT goods_id,goods_number FROM " . $ecs->table('order_goods') . " WHERE order_id = '$order_id'");
                    $sql = "UPDATE ".$ecs->table('goods'). " SET sort_sales = sort_sales - " . $goods['goods_number'] . " WHERE goods_id = '" . $goods['goods_id'] ."' ";
                    $sort_sales_res = $db->query($sql);
                    if(!$sort_sales_res){
                        $db->query("ROLLBACK"); //事务回滚
                        file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'减少销量失败' . $val ."\t" . date('Y-m-d H:i:s', time()), FILE_APPEND);
                        sys_msg($val.'减少销量失败');
                    }
                }
            }

            /* 退还用户余额、积分、红包 */

            $return_user_surplus_integral_bonus_res = return_user_surplus_integral_bonus($order);
            if (!$return_user_surplus_integral_bonus_res) {
                $db->query("ROLLBACK"); //事务回滚
                file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'积分退还失败' . $val ."\t" . date('Y-m-d H:i:s', time()), FILE_APPEND);
                sys_msg($val.'积分退还失败');
            }
            file_put_contents(ROOT_PATH . 'temp/common/order_cancle.log', PHP_EOL .'订单取消成功' . $val ."\t" . date('Y-m-d H:i:s', time()), FILE_APPEND);
            $db->query("COMMIT"); //提交事务
        }
    }
    sys_msg('订单取消成功');
}else{
    sys_msg('异常操作');
}

/**
 * 退回余额、积分、红包（取消、无效、退货时），把订单使用余额、积分、红包设为0
 * @param   array   $order  订单信息
 */
function return_user_surplus_integral_bonus($order)
{
    /* 处理余额、积分、红包 */
    if ($order['user_id'] > 0 && $order['surplus'] > 0)
    {
        $surplus = $order['money_paid'] < 0 ? $order['surplus'] + $order['money_paid'] : $order['surplus'];
        //是否代付
        if($order['share_sn']){
            $sql = "select user_id from " . $GLOBALS['ecs']->table('cash_payment_log') ." where order_id = '{$order['order_id']}' and pay_type = 9 and is_share = 1 limit 1";
            if($cash_payment_log = $GLOBALS['db']->getRow($sql)){
                $user_id = $cash_payment_log['user_id'];
            }else{
                return false;
            }
        }
        log_account_change($user_id ?: $order['user_id'], $surplus, 0, 0, 0, '已协商一致，同意取消订单并退款'.$order['order_sn'], ACT_REFUND_SURPLUS);
        //$GLOBALS['db']->query("UPDATE ". $GLOBALS['ecs']->table('order_info') . " SET `order_amount` = '0' WHERE `order_id` =". $order['order_id']);
    }

    if ($order['user_id'] > 0 && $order['integral'] > 0)
    {
        log_account_change($order['user_id'], 0, 0, 0, $order['integral'], sprintf($GLOBALS['_LANG']['return_order_integral'], $order['order_sn']));
    }

    if ($order['bonus_id'] > 0)
    {
        unuse_bonus($order['bonus_id']);
    }

    /* 修改订单 */
    $arr = array(
        'bonus_id'  => 0,
        'bonus'     => 0,
        'integral'  => 0,
        'integral_money'    => 0,
        //'surplus'   => 0
    );
    return update_order($order['order_id'], $arr);
}






?>
