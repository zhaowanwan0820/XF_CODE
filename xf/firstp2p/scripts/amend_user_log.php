<?php
/**
 * 平台账户金额修正（逻辑相关的lock_money全部为0，程序中不做考虑）
 * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php amend_user_log.php

 * @author wenyanlei 20140311
 */
require_once dirname(__FILE__).'/../app/init.php';

set_time_limit(0);

//平台关联账户id
$platid = 4159;
$platid = app_conf('DEAL_CONSULT_FEE_USER_ID') ? app_conf('DEAL_CONSULT_FEE_USER_ID') : $platid;

//平台关联账户的资金记录 起始记录
$start_info = $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."user_log WHERE user_id = ".$platid." and remaining_money != 0 ORDER BY id ASC limit 1");

$start_id = $start_info['id'];
$start_money = ($start_info['log_info'] == '平台代充值' ? ($start_info['remaining_money'] - $start_info['money']) : $start_info['remaining_money']);

//平台关联账户当前的账户余额
$user_money = -30869855.01;
$user_money = $GLOBALS['db']->getOne("SELECT money FROM ".DB_PREFIX."user WHERE id =".$platid);

//总的“平台代充值”金额
$pay_money = $GLOBALS['db']->getOne("SELECT sum(money) FROM ".DB_PREFIX."user_log WHERE user_id = ".$platid." and log_info = '平台代充值'");

//平台关联账户的资金记录
$logs = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."user_log WHERE user_id = ".$platid." and id > $start_id and is_delete = 0 and log_info != '平台代充值' ORDER BY id ASC");

//事务处理
$GLOBALS['db']->startTrans();

foreach($logs as $key => $val){
    
    if($key == 0){
        $front_money = $start_money;
    }
    
    $front_money = round($front_money + $logs[$key]['money'],2);
    
    $GLOBALS['db']->query("UPDATE ".DB_PREFIX."user_log SET remaining_money = ".$front_money.", remaining_total_money = ".$front_money." where id = ".$val['id']);
}

//数据验证
$check_money = round(abs($user_money - $pay_money - $front_money),2);

$msg = '金额差值：'.$check_money;

if($check_money == 0 || $check_money == 0.84){
    
    //删除“平台代充值”记录
    $GLOBALS['db']->query("UPDATE ".DB_PREFIX."user_log SET is_delete = 1 WHERE log_info = '平台代充值' AND user_id =".$platid);
    
    //更新用户money
    $GLOBALS['db']->query("UPDATE ".DB_PREFIX."user SET money = ".$front_money." WHERE id =".$platid);
    
    if($GLOBALS['db']->commit()){
        echo 'Success..',$msg;
    }else{
        echo 'faild...',$msg;
    }
}else{
    $GLOBALS['db']->rollback();
    echo 'faild.',$msg;
}

