<?php
/**
 * 发送红包组给指定用户
 */
if (time() > strtotime("2015-01-02 23:59:59")) {
    exit("已经发送30天\n.");
}
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\Logger;

@ini_set('memory_limit', '512M');
set_time_limit(0);

$num_success = 0;
$send_group_count = 20;
$bonus_service = new \core\service\BonusService();

$id_list = app_conf('BONUS_PUSH_GROUP_CHEDAI_LICAISHI');//红包推送的用户组
if(empty($id_list)) exit("组为空!\n");

//$user_name_list = '"15874878320","MeiZi","a381042314","duyuanbin","gianzi999","kangyuefei0614","lanhuahua520","lanzhou","laucy","lifeijuan","lulu33030688","maqing214","mikezhang1987","mryan123","mzmowen","panwei001","shellyif","stranger49","wanglingrio","wenbo1234","willieshong","yueyueniao123","zhaojianjian","zhuangmin"';

$sql = "SELECT `id`,`real_name`,`mobile` FROM ".DB_PREFIX."user WHERE id IN(".$id_list.") ORDER BY `id` ASC";
//定向用户组发红包
$list = $GLOBALS['db']->getAll($sql);
foreach ($list as $load_user){
    $msg = 'fail';

    $bonus_group_count = 0;//每个用户收到的红包组数量
    for($i = 0; $i < $send_group_count; $i++) {
        $res = $bonus_service->generation($load_user['id'], 0, 0, 0.25, 0, 1, 0, 0, 3);
        if($res) $bonus_group_count++;
    }
    //Logger::wLog(array('data' => "result:{$res}|uid:".$load_user['id'], 'script/bonus_send_all'));

    if($bonus_group_count){
        $num_success += $bonus_group_count;
        $msg = $bonus_group_count;
    }
    echo sprintf("%s|%s|%s\n", $load_user['id'], $load_user['mobile'], $msg);
}

echo '共', count($list),'个用户，红包发送成功',$num_success,"个\n";

