<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/1/19
 * Time: 11:27
 */
$startTime = time();
require dirname(__FILE__).'/../app/init.php';

es_session::close();
set_time_limit(0);
ini_set("display_errors", "On");
ini_set('memory_limit', '2048M');

//medal数据库的连接
$medalDb = new \libs\db\MysqlDb("172.31.35.100", "medal_pro", "auzCmyYtLhcMVX9z", "medal");
$mail = new \libs\mail\Mail();

$nowTime = time();
$charityDeals = $GLOBALS['db']->get_slave()->getAll("SELECT id FROM firstp2p_deal WHERE loantype = 7");
try{
    foreach($charityDeals as $charityDeal) {
        $medalValues = array();
        $createMedalSqls = "";
        $dealLoads = $GLOBALS['db']->get_slave()->getAll("SELECT user_id FROM firstp2p_deal_load WHERE deal_id={$charityDeal['id']}");
        foreach($dealLoads as $dealLoad) {
            $medalValues[] = "({$dealLoad['user_id']}, 435, {$nowTime}, {$nowTime}, 2, {$nowTime}, '435')";
        }
        if(count($medalValues)) {
            try{
                $createMedalSqls = sprintf("REPLACE INTO medal_user(user_id, medal_id, create_time, update_time, is_awarded, award_deadline, completed_rules) VALUES %s;",
                    implode(",", $medalValues));
//                var_dump($createMedalSqls);
                $medalDb->query($createMedalSqls);
            } catch(\Exception $e){
                echo $e->getMessage();
                $mail->send("创建'公益小鲜肉'勋章失败", $e->getMessage(), "dengyi@ucfgroup.com");
                exit;
            }
        }
    }
}catch (\Exception $e) {
    echo $e->getMessage();
    $mail->send("创建'公益小鲜肉'勋章失败", $e->getMessage(), "dengyi@ucfgroup.com");
    exit;
}
$endTime = time();
$costTime = $endTime - $startTime;
echo "end, costTime={$costTime}",PHP_EOL;
$mail->send("创建'公益小鲜肉'勋章完成", "costTime={$costTime}s", "dengyi@ucfgroup.com");

