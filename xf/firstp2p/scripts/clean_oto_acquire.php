<?php
/**
 * 执行方法/apps/product/php/bin/php clean_oto_acquire.php 60[保留的最大天数] 3000000[单次清理的最大数据量]
 * 清理oto_acquire_log表
 * 已领取的,需要联合confirm表确保真正完成兑换操作
 * acquireLog.gift_id>0 && confirmLog.updateTime>0
 *
 */
require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\PaymentApi;
use libs\utils\Logger;
error_reporting(E_ERROR);
set_time_limit(0);
ini_set('display_errors', 1);

//获取清理参数，可以按照天数保留数据，也可同时限定最大清理的数据量
$stopDay = isset($argv[1]) ? $argv[1] : 60;//默认删除2个月之前
$stopCount = isset($argv[2]) ? $argv[2] : 3000000;//默认删除300万数据

$stopTime = strtotime("-$stopDay days");
$stopCount = $stopCount;
$count = 0;
$logPrefix = "o2o_log_data_clean";
$acquireLogTable = 'firstp2p_oto_acquire_log';
$confirmLogTable = 'firstp2p_oto_confirm_log';
PaymentApi::log($logPrefix. "|start|". date("Y-m-d H:i:s"));

$p2pdb = \libs\db\Db::getInstance('firstp2p','master','utf8',1);
$acquireSql = "select id,gift_id,create_time,deal_load_id,expire_time,extra_info,gift_code,gift_group_id,request_resend_count,request_status,status,trigger_mode,update_time,user_id from $acquireLogTable order by id asc limit 1";
$result = $p2pdb->query($acquireSql);

while($result && ($data = $p2pdb->fetchRow($result))) {
    if(($data['create_time'] > $stopTime) || ($count >= $stopCount)) {
        //记录退出时的状态
        PaymentApi::log($logPrefix. "|break|create_time:{$data['create_time']}|stopTime:$stopTime|count:$count|stopCount:$stopCount|".json_encode($data,JSON_UNESCAPED_UNICODE));
        break;
    }

    if($data['gift_id'] > 0) {
        $confirmSql = "select id,update_time,create_time,gift_code,gift_id,store_id,user_id from $confirmLogTable where gift_id={$data['gift_id']}";
        $confirmItem = $p2pdb->fetchRow($p2pdb->query($confirmSql));
        if($confirmItem && $confirmItem['update_time'] >0) {
            //已经领取并转账，直接删除
            $acquireDelSql = "delete from $acquireLogTable where id={$data['id']}";
            $p2pdb->query($acquireDelSql);
            if($p2pdb->affected_rows()) {
                PaymentApi::log($logPrefix. '|del_acquire_data|'.json_encode($data,JSON_UNESCAPED_UNICODE));
                $confirmDelSql = "delete from $confirmLogTable where id = {$confirmItem['id']}";
                $p2pdb->query($confirmDelSql);
                if($p2pdb->affected_rows()){
                    PaymentApi::log($logPrefix. '|del_confirm_data|'.json_encode($confirmItem,JSON_UNESCAPED_UNICODE));
                }
                $count++;
            }
        }
    }

    //循环下一条记录，以当前记录id向上遍历
    $acquireSql = "select id ,gift_id,create_time ,deal_load_id,expire_time,extra_info,gift_code,gift_group_id,request_resend_count,request_status,status,trigger_mode,update_time,user_id from $acquireLogTable where id>{$data['id']} order by id asc limit 1";
    $result = $p2pdb->query($acquireSql);
}

PaymentApi::log($logPrefix. '|count|'.$count);
PaymentApi::log($logPrefix. "|end|". date("Y-m-d H:i:s"));
