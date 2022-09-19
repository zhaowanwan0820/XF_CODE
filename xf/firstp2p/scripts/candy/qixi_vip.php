<?php
/**
 * 2018七夕vip邀请奖信力活动
 */
ini_set('display_errors', 'On'); error_reporting(E_ALL);
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once dirname(__FILE__).'/../../app/init.php';
use libs\db\Db;
use core\service\candy\CandyActivityService;

// 写日志
function logMessage($message) {
   return \libs\utils\Logger::info("CANDY_QIXI_VIP:" . $message);
}

// 判断用户是否已领取奖励
function hadDone($token) {
    $sql = "SELECT * FROM activity_log WHERE token = '{$token}'";
    $activityInfo  = Db::getInstance('candy', 'master')->getRow($sql);
    if (empty($activityInfo)) {
        return false;
    }

    return true;
}

// 判断是不是VIP
function isVip($userId) {
    // VIP捞取
    $sql = "SELECT user_id FROM firstp2p_vip_account WHERE service_grade > 0 AND user_id = '$userId'";
    $vipInfo = Db::getInstance('vip', 'slave')->getRow($sql);
    if (empty($vipInfo)) {
        return false;
    }

    return true;
}

$timeStart = strtotime(CandyActivityService::$qixiVipConf['startDate']);
$timeEnd = strtotime(CandyActivityService::$qixiVipConf['endDate']) + 86400;

$candyDB = Db::getInstance('candy', 'master');
// 获取区间邀请首投大于两次的用户
$sql = "SELECT user_id FROM activity_log WHERE create_time >= $timeStart AND create_time < $timeEnd AND source_type = " . CandyActivityService::SOURCE_TYPE_INVITE . " GROUP BY user_id HAVING COUNT(user_id) >=2";
//$sql = "SELECT user_id FROM activity_log WHERE source_type = " . CandyActivityService::SOURCE_TYPE_INVITE . " GROUP BY user_id HAVING COUNT(user_id) >=2";

$matchUser = $candyDB->getAll($sql);
if (empty($matchUser)) {
    // 满足条件数据为空
    logMessage('NO MATCH DATA');
    return false;
}

$candyActivityService = new CandyActivityService();
foreach ($matchUser as $user) {
    $token = 'INVITE_2018_QIXI_VIP_' . $user['user_id'];
    // 是否已经奖励过信力
    if (hadDone($token)) {
        logMessage("{$user['user_id']} HAD DONE");
        continue;
    }

    // vip验证
    if (!isVip($user['user_id'])) {
        logMessage("{$user['user_id']} NO VIP");
        continue;
    }

    try {
        $res = $candyActivityService->addActivity($token, $user['user_id'], CandyActivityService::$qixiVipConf['activity'], CandyActivityService::SOURCE_TYPE_INVITE, "信宝VIP七夕活动奖励");
        if (!$res) {
            throw new \Exception('信力添加异常');
        }
        logMessage("{$user['user_id']} ADD ACTIVITY " . CandyActivityService::$qixiVipConf['activity']);
    } catch(\Exception $e) {
        logMessage("{$user['user_id']} ADD ACTIVITY EXCEPTION, " . $e->getMessage());
        // 如果不是重复插入，抛异常中断
        if (strpos($e->getMessage(), 'Duplicate entry') === false)  {
            throw $e;
        }
    }
}
