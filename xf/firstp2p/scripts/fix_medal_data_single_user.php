<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/3/1
 * Time: 14:42
 */
ini_set("display_errors", "Off");
set_time_limit(0);
ini_set('memory_limit', '2048M');

require dirname(__FILE__).'/../app/init.php';
es_session::close();
const KEY_FORMAT = "/progress/%s/%s";  //   /progress/userId/medalId
$medalId = 453; //线上medalID 453。 Duang~
$medalRuleId = 453; // 线上的medalRuleId 453;
$prizeDeadLine = 30;
$title = "获得勋章";
$content = "恭喜您成功点亮勋章【Duang~】，部分勋章有奖励请前往查看。";
//medal数据库的连接
$medalDb = new \libs\db\MysqlDb("172.31.35.100", "medal_pro", "auzCmyYtLhcMVX9z", "medal"); //online
$mail = new \libs\mail\Mail();

//redis 初始化
$sentinel = array( //online
    array(
        'host' => 'st-redis1.wxlc.org',
        'port' => '26479',
    ),
    array(
        'host' => 'st-redis2.wxlc.org',
        'port' => '26479',
    ),
    array(
        'host' => 'st-redis3.wxlc.org',
        'port' => '26479',
    ),
);
$masterName = "mymaster";//online
$ncfRedis = new \libs\caching\NcfRedis();
$ncfRedis->setSentinels($sentinel);
$clusters = array();
$redis = @$ncfRedis->getClusterConnection($masterName, $clusters, 5, true);

//STEP 1： 找到3月1号投资的用户
//出错的时间段

$startDate = "20160301"; //开始日期
$totalDays = 30; //总共天数

$startErrorTime = to_timespan($startDate);  //北京时间3月1号凌晨0点整
$endErrorTime = $startErrorTime + 16 * 3600 + 600; //北京时间下午4点10分
//获取出错的时间段的投资用户ID。
$userId = 115; // 高阳的userId。

try{
    //STEP 2: 对每个3月1号投资的用户，修复数据。
    $userInvestDaysArray = array();
    for ($i = 1; $i < $totalDays; $i++) {
        $startTime = @strtotime("-{$i} day", @strtotime($startDate));
        $endTime = $startTime + 86400 - 1;
        //TODO: 找到这天有没有投资记录
        $startTimeLimit = $startTime - 8 * 3600;
        $endTimeLimit = $endTime - 8 * 3600 - 1;
        $userDealLoads = $GLOBALS['db']->get_slave()->getAll("SELECT id FROM firstp2p_deal_load WHERE user_id = {$userId}
            AND (create_time >= {$startTimeLimit} AND create_time <= {$endTimeLimit}) LIMIT 1");
        if (empty($userDealLoads)) {
            break;
        }
        $userInvestDaysArray[$i] = date("Ymd", $startTime);
    }
    //获取用户连续投资的天数。
    $count = count($userInvestDaysArray);
    if ($count != 0) {
        //更新进度。
        $key = sprintf(KEY_FORMAT, $userId, $medalId);
        $field = $medalRuleId;
        $oldValue = $redis->hGet($key, $field);
        $redis->hIncrby($key, $field, $count); //把删掉的连续投资记录天数加回去。
        $newValue = $redis->hGet($key, $field);
        \libs\utils\logger::error("MedalLogger: Update Medal Progress, User={$userId}, oldValue={$oldValue}, newValue={$newValue}");
        //如果能拿到勋章，则给用户发勋章。
        if ($newValue >= $totalDays) {
            $nowTime = time();
            $deadline = $nowTime + $prizeDeadLine * 86400;
            $createMedalSql = sprintf('INSERT INTO medal_user(user_id, medal_id, create_time, update_time, is_awarded, award_deadline, completed_rules) VALUES (%d, %d, %d, %d, %d, %d, "%s");',
                $userId, $medalId, $nowTime, $nowTime, 2, $deadline, $medalRuleId);
            $medalDb->query($createMedalSql);
            $msgBoxService = new core\service\MsgBoxService();
            $msgBoxService->create($userId, 36, $title, $content);
            $mail->send("创建勋章成功", "create medal for user={$userId}", "dengyi@ucfgroup.com");
        }
    }
} catch (Exception $e) {
    $mail->send("修复数据报错", $e->getMessage(), "dengyi@ucfgroup.com");
}

$mail->send("修复单个用户数据的脚本，执行结束", "恭喜，查看是否成功", "dengyi@ucfgroup.com");


