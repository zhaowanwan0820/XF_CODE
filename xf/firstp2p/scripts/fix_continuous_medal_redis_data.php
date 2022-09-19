<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/14
 * Time: 14:30
 */
ini_set("display_errors", "Off");
set_time_limit(0);
ini_set('memory_limit', '2048M');

require dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__) . "/../libs/utils/PhalconRPCInject.php";
es_session::close();
\libs\utils\PhalconRpcInject::init();

//多天连续累计
$continuousMedalMap = array(
    450  => 'continuous_days', /*城会玩*/
    453  => 'continuous_days', /*Duang~*/
);

$progressKeyFormat = "/progress/%s/%s";

//medalRedis 初始化
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
$medalRedis = @$ncfRedis->getClusterConnection($masterName, $clusters, 5, true);

//taskRedis 初始化
$taskRedisHost = "172.21.12.28";
$taskRedisPort = "6378";
$timeout = 1;
$password = "012_345678-90";
$taskRedis = new \Redis();
$result = $taskRedis->connect($taskRedisHost, $taskRedisPort, $timeout);
if(!$result) {
    exit("无法连接task的redis");
}
$result = $taskRedis->auth($password);
if(!$result) {
    exit("无法通过密码验证");
}
//task的redis用的是DB 1。
$taskRedis->select(1);

//medal数据库的连接
$medalDb = new \libs\db\MysqlDb("medal.wxlc.org", "medal_pro", "auzCmyYtLhcMVX9z", "medal"); //online

//发邮件报告进度，否则两眼一抹黑，不知道啥情况。
$mail = new \libs\mail\Mail();
echo "修复连续型勋章数据的脚本，执行开始\n";
$mail->send("修复连续型勋章数据的脚本，执行开始", "已经进入修复阶段", "dengyi@ucfgroup.com");

try {
    $userIds = array();
    foreach($continuousMedalMap as $medalId => $keyWord) {
        $progressWildcardKey = sprintf($progressKeyFormat, "*", $medalId);
        //获得某个勋章受影响的的进度。
        $progressKeys = $taskRedis->keys($progressWildcardKey);
        $count = count($progressKeys);
        //TODO: 打印$progressWildcardKey, $count。
        mylog("medalId={$medalId}, count={$count}, progressWildcardKey={$progressWildcardKey}");
        foreach($progressKeys as $progressKey) {
            //获取收影响的userId，并保存到userIds中。
            if(preg_match("/\/progress\/(?<userId>\d+)\/\d+/", $progressKey, $matches)) {
                $userId = $matches['userId'];
                $userIds[$userId] = 1;
            } else {
                echo "无法找到userId的信息, progressKey={$progressKey}\n";
                $mail->send("无法找到userId的信息, progressKey={$progressKey}", "无法找到userId的信息, progressKey={$progressKey}", "dengyi@ucfgroup.com");
            }
        }
    }
    //统计受影响的用户的数量。
    $count = count($userIds);
    //TODO: 打印日志，受影响的用户数量。
    mylog("userCount={$count}, medalId=450/453");

    //对每个受影响的用户，计算其连续投资的天数。
    $userIds = array_keys($userIds);
    foreach($userIds as $userId) {
        //计算userId的用户连续投资的天数。
        $startDate = "20160310"; //到20160412有32天。
        $maxUpdateDate = $startDate;  //最长连续投资的日期
        $maxContinuousDays = 0;   //最长连续投资的天数
        $preMaxContinuousDays = 0; //上一次最长连续投资的天数。
        $hasMedal450 = false; //城会玩勋章，连续投资15天，默认都没有获得该勋章。
        $hasMedal453 = false; //Duang~勋章，连续投资30天，默认都没有获得该勋章。
        while(@strtotime($startDate) <= @strtotime(@date("Ymd"))) {
            $startTime = @strtotime($startDate);
            $endTime = $startTime + 86400 - 1;
            $startTimeLimit = $startTime - 8 * 3600;
            $endTimeLimit = $endTime - 8 * 3600;
            //获取用户$startTime ~ $endTime这天是否有投资。
            $userDealLoads = $GLOBALS['db']->get_slave()->getAll("SELECT id FROM firstp2p_deal_load WHERE user_id = {$userId}
            AND (create_time >= {$startTimeLimit} AND create_time <= {$endTimeLimit}) LIMIT 1");
            if(empty($userDealLoads)) {//没投资
                //记录上一次的最大连续天数，如果maxContinuousDays = 0，则使用preMaxContinuousDays作为结果。
                $preMaxContinuousDays = $maxContinuousDays;
                //连续投资天数断了，所以$maxContinuousDays清零。
                $maxContinuousDays = 0;
                mylog("userId={$userId}, startDate={$startDate}, maxContinuousDays={$maxContinuousDays}, preMaxContinuousDays={$preMaxContinuousDays}, maxUpdateDate={$maxUpdateDate}");
            } else {//有投资
                //更新最长的更新天数
                $maxContinuousDays++;
                //记录最长的更新日期。
                $maxUpdateDate = $startDate;
                mylog("userId={$userId}, startDate={$startDate}, maxContinuousDays={$maxContinuousDays}, preMaxContinuousDays={$preMaxContinuousDays}, maxUpdateDate={$maxUpdateDate}");
            }
            //更新$startDate。
            $startDate = @date("Ymd", @strtotime("1 day", @strtotime($startDate)));
            //TODO: 如果达到了拿勋章的条件，则给用户发勋章。
            if($maxContinuousDays >= 15 && $hasMedal450 == false) {
                //TODO: 更新进度。
                $progressKey = sprintf($progressKeyFormat, $userId, 450); //450勋章更新进度
                $updateResult = $medalRedis->hMset($progressKey, array(450 => $maxContinuousDays, "update_date" => $maxUpdateDate));
                //TODO: 打印更新结果。
                mylog("progressKey={$progressKey}, medalRuleId=450, continuousDays={$maxContinuousDays}, update_date={$maxUpdateDate}, updateResult={$updateResult}");
                //TODO: 给用户发450勋章和推送
                $result = grantUserMedal($userId, 450);
                //TODO: 记录日志。
                mylog("grant user medal, userId={$userId}, medalId=450, result={$result}");
                if(!$result) {
                    echo "grant user medal failed, userId={$userId}, medalId=450, result={$result}\n";
                    $mail->send("grant user medal failed, userId={$userId}, medalId=450, result={$result}",
                        "grant user medal failed, userId={$userId}, medalId=450, result={$result}", "dengyi@ucfgroup.com");
                }
                $hasMedal450 = true;
            }
            if($maxContinuousDays >= 30 && $hasMedal453 == false) {
                //TODO: 更新进度。
                $progressKey = sprintf($progressKeyFormat, $userId, 453); //453勋章更新进度
                $updateResult = $medalRedis->hMset($progressKey, array(453 => $maxContinuousDays, "update_date" => $maxUpdateDate));
                //TODO: 打印更新结果。
                mylog("progressKey={$progressKey}, medalRuleId=453, continuousDays={$maxContinuousDays}, update_date={$maxUpdateDate}, updateResult={$updateResult}");
                //TODO: 给用户发453勋章和推送
                $result = grantUserMedal($userId, 453);
                //TODO: 记录日志。
                mylog("grant user medal, userId={$userId}, medalId=453, result={$result}");
                if(!$result) {
                    echo "grant user medal failed, userId={$userId}, medalId=453, result={$result}\n";
                    $mail->send("grant user medal failed, userId={$userId}, medalId=453, result={$result}",
                        "grant user medal failed, userId={$userId}, medalId=453, result={$result}", "dengyi@ucfgroup.com");
                }
                $hasMedal453 = true;
                //获得了453勋章了，则可以中断循环了。
                break;
            }
        }
        //如果最后一天没有投资，则用上一次最长投资天数
        if($maxContinuousDays == 0) {
            $maxContinuousDays = $preMaxContinuousDays == 0 ? 1 : $preMaxContinuousDays;
        }
        //TODO: $maxContinuousDays和$maxUpdateDate更新到medalRedis
        if($hasMedal450 == false) {
            //TODO: 更新进度。
            $progressKey = sprintf($progressKeyFormat, $userId, 450); //450勋章更新进度
            $updateResult = $medalRedis->hMset($progressKey, array(450 => $maxContinuousDays, "update_date" => $maxUpdateDate));
            //TODO: 打印更新结果。
            mylog("progressKey={$progressKey}, medalRuleId=450, continuousDays={$maxContinuousDays}, update_date={$maxUpdateDate}, updateResult={$updateResult}");
        }
        if($hasMedal453 == false) {
            //TODO: 更新进度。
            $progressKey = sprintf($progressKeyFormat, $userId, 453); //453勋章更新进度
            $updateResult = $medalRedis->hMset($progressKey, array(453 => $maxContinuousDays, "update_date" => $maxUpdateDate));
            //TODO: 打印更新结果。
            mylog("progressKey={$progressKey}, medalRuleId=453, continuousDays={$maxContinuousDays}, update_date={$maxUpdateDate}, updateResult={$updateResult}");
        }
    }

} catch(\Exception $e) {
    //TODO: 打印日志和发送邮件
    mylog("errMsg=" . $e->getMessage() . ", line=" . $e->getLine());
    echo "errMsg=" . $e->getMessage() . ", line=" . $e->getLine() . "\n";
    $mail->send("errMsg=" . $e->getMessage() . ", line=" . $e->getLine(), "errMsg=" . $e->getMessage() . ", line=" . $e->getLine(), "dengyi@ucfgroup.com");
}
echo "修复连续型勋章数据的脚本，执行结束\n";
$mail->send("修复连续型勋章数据的脚本，执行结束", "执行结束", "dengyi@ucfgroup.com");

function grantUserMedal($userId, $medalId) {
    $service = "\\NCFGroup\\Medal\\Services\\Medal";
    $method = "grantUserMedal";
    $request = new \NCFGroup\Protos\Medal\RequestGrantUserMedal();
    $request->setUserId(intval($userId));
    $request->setMedalId(intval($medalId));
    $request->setIsPush(true);
    try{
        $result = $GLOBALS['medalRpc']->callByObject(array(
            'service' => $service,
            'method' => $method,
            'args' => $request,
        ));
        return $result;
    }catch (\Exception $e) {
        return false;
    }
}

function mylog($content) {
    $prefix = "MedalFixData======>";
    $content = $prefix . $content;
    \libs\utils\logger::error($content);
    echo $content, PHP_EOL;
}
