<?php
/**
 * 发送红包组给指定用户
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\Logger;

set_time_limit(0);

//$sql = "SELECT `id`,`real_name`,`mobile` FROM ".DB_PREFIX."user WHERE `id` IN (SELECT DISTINCT(`user_id`) FROM ".DB_PREFIX."deal_load WHERE `money` >= 100) AND `is_effect` = 1 AND `is_delete` = 0 ORDER BY `id` ASC";

//不在红包活动范围内则停止发送
$now = time();
$bonus_start = strtotime(app_conf('BONUS_START_TIME'));
$bonus_end = strtotime(app_conf('BONUS_END_TIME'));
if($now < $bonus_start || $now > $bonus_end){
    echo "活动已结束";
    exit;
}

$bonus_group_total = 1;//app_conf('BONUS_PUSH_TOTAL');//每人收到的红包组个数
//if($bonus_group_total <= 0) exit;

//$bonus_groups = app_conf('BONUS_PUSH_GROUP');//红包推送的用户组
//if(empty($bonus_groups)) exit;

//定向用户组发红包
$sql = "SELECT `id`,`real_name`,`mobile` FROM ".DB_PREFIX."user WHERE `group_id` IN(32,26,25,14,12,11) AND `is_effect` =1 AND `is_delete` =0 ORDER BY `id` ASC ";
$list = $GLOBALS['db']->get_slave()->getAll($sql);

//$chunk_arr = array_chunk($list, 1000);
$bonus_service = new \core\service\BonusService();

//$num_send = 0;
$num_success = 0;

//$msgcenter = new msgcenter();
foreach ($list as $load_user){
    $msg = 'fail';
    
    $bonus_group_count = 0;//每个用户收到的红包组数量
    for($i=0;$i<$bonus_group_total;$i++){
        $res = $bonus_service->generation($load_user['id'], 0, 0, 0.25, 0, 1, 30, 10);
        if($res) $bonus_group_count++;
        Logger::wLog(array('data' => "result:{$res}|uid:".$load_user['id'], 'script/bonus_send_new'));
    }
    //&& $load_user['mobile']
    if($bonus_group_count){
        /* $params = array(
                'url' => PRE_HTTP.APP_HOST.'/account/bonus',
        ); */
        //$msgcenter->setMsg($load_user['mobile'], $load_user['id'], $params, 'TPL_SMS_BONUS_SEND', '赠送红包通知');
        $num_success += $bonus_group_count;
        $msg = $bonus_group_count;
    }
    //echo sprintf("%s|%s\n", $load_user['mobile'], $msg);
}
//$msgcenter->save();
//unset($msgcenter);

echo '共',count($list),'个用户，红包发送成功',$num_success,"个\n";

