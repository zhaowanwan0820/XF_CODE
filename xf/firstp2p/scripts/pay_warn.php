<?php
//crontab: */20 * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php pay_warn.php

/**
 * 检查20分钟内未完成支付的充值订单，并发送邮件及短信报警
 * 未完成支付的计算方法：上一个20分钟内(40分钟前至20分钟前)下的充值订单至当前时间仍未完成支付者
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com> 2013年09月25日 14:27:28
 * @version $Id$
 **/

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

set_time_limit(0);

$time_start = get_gmtime() - 40 * 60;   //从40分钟前开始计算
$time_end = get_gmtime() - 20 * 60;     //到20分钟前结束结算

//$time_start= mktime(0, 0, 0, 8, 23, 2013);
//$time_end = mktime(0, 0, 0, 9, 19, 2013);

/**
 * 一段计时周期内下的单，至今未完成支付的订单列表
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return mixed
 **/
function failed_deal_orders($time_start, $time_end)
{
    $sql = "select order_sn, user_id, ".DB_PREFIX."user.user_name, ".DB_PREFIX."user.mobile, ".DB_PREFIX."user.real_name, deal_total_price, bank_id, ".DB_PREFIX."deal_order.create_time 
    from ".DB_PREFIX."deal_order
    left join ".DB_PREFIX."user on ".DB_PREFIX."user.id = ".DB_PREFIX."deal_order.user_id
    where pay_status!=2 and ".DB_PREFIX."deal_order.create_time > $time_start and ".DB_PREFIX."deal_order.create_time < $time_end";

    return $GLOBALS['db']->getAll($sql);
}

$failed_orders = failed_deal_orders($time_start, $time_end);
if($failed_orders)
{
    FP::import("libs.common.dict");
    $Msgcenter = new Msgcenter();
    foreach ($failed_orders as $data) {
        $data["create_time"] = to_date($data["create_time"]); 
        $title = "充值未成功";
        foreach (dict::get("PAY_WARN_EMAIL") as $email) {
            $Msgcenter->setMsg($email, 0, $data, "TPL_PAY_WARN_MAIL", $title);
        }
        //foreach (dict::get("PAY_WARN_MOBILE") as $mobile) {
        //    $Msgcenter->setMsg($mobile, 0, $data, "TPL_PAY_WARN_SMS", $title);
        //}
    }
    $Msgcenter->save();
}
