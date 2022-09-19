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

//多次连续累计。
$accumulateMedalMap = array(
    432  => 'total_money', /*包养你*/
    438  => 'total_money', /*靠脸吃饭*/
    447  => 'total_persons', /*主要看气质*/
    441  => 'total_money', /*真爱粉*/
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
echo "修复累加型勋章数据的脚本，执行开始\n";
$mail->send("修复累加型勋章数据的脚本，执行开始", "已经进入修复阶段", "dengyi@ucfgroup.com");

try {
    //处理累加的勋章，4个。
    foreach($accumulateMedalMap as $medalId  => $keyWord) {
        //取勋章。
        $medal = $medalDb->getRow("SELECT * FROM medal_info WHERE id = {$medalId}");
        if(empty($medal)) {
            echo "无法找到medal的信息, medalId={$medalId}\n";
            $mail->send("无法找到medal的信息, medalId={$medalId}", "无法找到medal的信息, medalId={$medalId}", "dengyi@ucfgroup.com");
            continue;
        }
        mylog("process medalId={$medalId}");
        //取勋章的规则。
        $medalRule = $medalDb->getRow("SELECT * FROM medal_rules WHERE medal_id = {$medalId}");
        if(empty($medalRule)) {
            echo "无法找到medalRule的信息, medalId={$medalId}\n";
            $mail->send("无法找到medalRule的信息, medalId={$medalId}", "无法找到medalRule的信息, medalId={$medalId}", "dengyi@ucfgroup.com");
            continue;
        }
        //通配符查找受影响的key，key: /progress/*/medalId
        $progressWildcardKey = sprintf($progressKeyFormat, "*"/*通配符,表示userId*/, $medalId);
        mylog("progressWildcardKey={$progressWildcardKey}");
        //获得某个勋章受影响的的进度。
        $progressKeys = $taskRedis->keys($progressWildcardKey);
        $count = count($progressKeys);
        //TODO: 打印$progressWildcardKey, $count。
        mylog("medalId={$medalId}, count={$count}, progressWildcardKey={$progressWildcardKey}");
        foreach($progressKeys as $progressKey) {
            //先从medalRedis读，如果有记录了，则说明这个key已经修复过了
            if($medalRedis->get(md5($progressKey))) {
                mylog("duplicate key={$progressKey}");
                continue;
            }
            //读取taskRedis中的数据。
            $wrongValue = $taskRedis->hGet($progressKey, $medalRule['id']);
            mylog("progressKey={$progressKey}, medalRuleId={$medalRule['id']}, wrongValue={$wrongValue}");
            if($wrongValue <= 0) {
                echo "无法找到进度, progressKey={$progressKey}, medalRuleId={$medalRule['id']}\n";
                $mail->send("无法找到进度, progressKey={$progressKey}, medalRuleId={$medalRule['id']}",
                    "无法找到进度, progressKey={$progressKey}, medalRuleId={$medalRule['id']}", "dengyi@ucfgroup.com");
                continue;
            }
            //将错误的key做标记，做幂等。脚本可以多次执行了。
//            $taskRedis->del($progressKey);
//            mylog("delete progressKey={$progressKey}");
            $result = $medalRedis->setEx(md5($progressKey), 86400, 1);
            //读取medalRedis中的值。
            $oldValue = $medalRedis->hGet($progressKey, $medalRule['id']);
            mylog("progressKey={$progressKey}, medalRuleId={$medalRule['id']}, oldValue={$oldValue}");
            //将wrongValue更新到medalRedis中去。增量更新。
            $newValue = $medalRedis->hIncrby($progressKey, $medalRule['id'], $wrongValue);
            mylog("progressKey={$progressKey}, medalRuleId={$medalRule['id']}, newValue={$newValue}");
            //TODO：打印日志，把$progressKey, $medalRule['id'], $wrongValue, $oldValue, $newValue都打印出来
            mylog("progressKey={$progressKey}, medalRuleId={$medalRule['id']}, wrongValue={$wrongValue}, oldValue={$oldValue}, newValue={$newValue}");
            if($newValue >= $medalRule[$keyWord]) {//勋章条件达到。
                //TODO: 发送勋章和推送，并打印Log。
                if(preg_match("/\/progress\/(?<userId>\d+)\/\d+/", $progressKey, $matches)) {//获取用户的ID。
                    $userId = $matches['userId'];
                    $result = grantUserMedal($userId, $medalId);
                    mylog("grant user medal, userId={$userId}, medalId={$medalId}, result={$result}");
                    if(!$result) {
                        echo "grant user medal failed, userId={$userId}, medalId={$medalId}, result={$result}\n";
                        $mail->send("grant user medal failed, userId={$userId}, medalId={$medalId}, result={$result}",
                            "grant user medal failed, userId={$userId}, medalId={$medalId}, result={$result}", "dengyi@ucfgroup.com");
                    }
                } else {
                    echo "无法找到userId的信息, progressKey={$progressKey}\n";
                    $mail->send("无法找到userId的信息, progressKey={$progressKey}", "无法找到userId的信息, progressKey={$progressKey}", "dengyi@ucfgroup.com");
                }
            }
        }
    }
} catch(\Exception $e) {
    //TODO: 打印日志和发送邮件
    mylog("errMsg=" . $e->getMessage() . ", line=" . $e->getLine());
    echo "errMsg=" . $e->getMessage() . ", line=" . $e->getLine() . "\n";
    $mail->send("errMsg=" . $e->getMessage() . ", line=" . $e->getLine(), "errMsg=" . $e->getMessage() . ", line=" . $e->getLine(), "dengyi@ucfgroup.com");
}
echo "修复累加型勋章数据的脚本，执行结束\n";
$mail->send("修复累加型勋章数据的脚本，执行结束", "执行结束", "dengyi@ucfgroup.com");

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
