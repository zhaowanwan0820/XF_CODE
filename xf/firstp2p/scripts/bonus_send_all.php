<?php
/**
 * 发送红包组给指定用户
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\Logger;
use core\service\BonusService;

@ini_set('memory_limit', '512M');
set_time_limit(0);

$num_success = 0;
$bonus_service = new \core\service\BonusService();
$count = 10000;
if (intval($argv[1]) > 0) {
    $count = intval($argv[1]);
}
$count_user = 0;

//$start_time = to_timespan('2014-11-01 00:00:00');
//$end_time = to_timespan('2014-11-30 23:59:59');
$bonus_groups = app_conf('BONUS_PUSH_GROUP');//红包推送的用户组
$limit_time = to_timespan('2014-12-09 00:00:00');
$bonus_group_total = 2;
//if (intval($argv[2]) > 0) {
//    $bonus_group_total = intval($argv[2]);
//}
if(empty($bonus_groups)) exit;

for ($i = 0; $i < 100; $i++) {

    $sql = "SELECT `id`,`real_name`,`mobile` FROM ".DB_PREFIX."user WHERE group_id NOT IN(".$bonus_groups.") AND create_time < $limit_time AND `is_effect` =1 AND `is_delete` =0 ORDER BY `id` ASC LIMIT ". ($count * $i) . "," . $count;
    //定向用户组发红包
    $list = $GLOBALS['db']->getAll($sql);
    $current_count = count($list);
    $count_user += $current_count;
    if ($current_count <= 0) {
        break;
    }
    foreach ($list as $load_user){
        $msg = 'fail';

        $bonus_group_count = 0;//每个用户收到的红包组数量
        for($j=0;$j<$bonus_group_total;$j++){
            $res = $bonus_service->generation($load_user['id'], 0, 0, 0.25, 0, 1, 0, 0, 2);
            if($res) $bonus_group_count++;
            //Logger::wLog(array('data' => "result:{$res}|uid:".$load_user['id'], 'script/bonus_send_all'));
        }
        if($bonus_group_count){
            $num_success += $bonus_group_count;
            $msg = $bonus_group_count;
        }
        echo sprintf("%s|%s|%s\n", $load_user['id'], $load_user['mobile'], $msg);
    }
    usleep(10000);
}

echo '共', $count_user,'个用户，红包发送成功',$num_success,"个\n";

