<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/1/25
 * Time: 20:30
 */
$startTime = time();
require dirname(__FILE__).'/../app/init.php';

es_session::close();
set_time_limit(0);
ini_set('memory_limit', '2048M');
ini_set("display_errors", "On");

const KEY_CURRENT_USER_ID_FOR_MEDAL = "current_user_id_for_medal";

//medal数据库的连接
$medalDb = new \libs\db\MysqlDb("172.31.35.100", "medal_pro", "auzCmyYtLhcMVX9z", "medal");
$mail = new \libs\mail\Mail();

//初始化redis
$redis = \SiteApp::init()->dataCache->getRedisInstance();
if(!$redis) {
    throw new \Exception("Can not initialize redis");
}

$maxUserId = $GLOBALS['db']->get_slave()->getOne("SELECT id FROM firstp2p_user ORDER BY id DESC LIMIT 1;");
//var_dump($maxUserId);

//获取当前已经消费过的ID
$currentUserId = $redis->get(KEY_CURRENT_USER_ID_FOR_MEDAL);

if(empty($currentUserId)) {
    //获取最小的ID
    $currentUserId = $GLOBALS['db']->get_slave()->getOne("SELECT id FROM firstp2p_user ORDER BY id ASC LIMIT 1;");
}
//var_dump($currentUserId);
$nowTime = time();
$limit = 500;

$medalValues = array();

$lastSendMailTime = time();
try {
    while($currentUserId < $maxUserId) {
        $userDealLoads = $GLOBALS['db']->get_slave()->getAll("SELECT id FROM firstp2p_deal_load WHERE user_id = {$currentUserId} AND (source_type = 3 OR source_type = 4) LIMIT 1");
        if($userDealLoads) {
            $medalValues[] = "({$currentUserId}, 429, {$nowTime}, {$nowTime}, 2, {$nowTime}, '429')";
        }
        //执行SQL，往Medal里面灌数据
        if(count($medalValues) >= $limit) {
            try{
                $createMedalSqls = sprintf("REPLACE INTO medal_user(user_id, medal_id, create_time, update_time, is_awarded, award_deadline, completed_rules) VALUES %s;",
                    implode(",", $medalValues));
//            var_dump($createMedalSqls);
                $medalValues = array();
                $medalDb->query($createMedalSqls);
            } catch(\Exception $e){
                echo $e->getMessage();
                $mail->send("创建'掌控者'勋章失败", $e->getMessage(), "dengyi@ucfgroup.com");
                exit;
            }
        }
        $currentTime = time();
        if($currentTime > $lastSendMailTime + 1800) {//每隔半小时发邮件，报告进度
            $lastSendMailTime = $currentTime;
            $mail->send("创建'掌控者'勋章进度报告", "当前创建到用户{$currentUserId}", "dengyi@ucfgroup.com");
        }
        //更新currentLoadId
        $currentUserId = $currentUserId + 1;
//    var_dump($currentUserId);
        //写到redis
        $redis->setEx(KEY_CURRENT_USER_ID_FOR_MEDAL, 86400 * 3, $currentUserId);
    }
} catch (\Exception $e) {
    echo $e->getMessage();
    $mail->send("创建'掌控者'勋章失败", $e->getMessage(), "dengyi@ucfgroup.com");
    exit;
}

$endTime = time();
$costTime = $endTime - $startTime;
echo "end, maxUserId={$maxUserId}, costTime={$costTime}", PHP_EOL;
$mail->send("创建'掌控者'勋章成功", "end, maxUserId={$maxUserId}, costTime={$costTime}s", "dengyi@ucfgroup.com");
