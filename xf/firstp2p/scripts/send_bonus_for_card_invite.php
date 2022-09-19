<?php
/**
 * JIRA : http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-1813
 * 给纸质红包推荐人补发10元红包
 */
use \core\dao\BonusModel;
use \core\dao\UserModel;
use \core\dao\BonusConfModel;
use \core\service\CouponService;
use \libs\utils\Logger;
require(dirname(__FILE__) . '/../app/init.php');
ini_set('error_reporting', E_ERROR);

$step = $argv[1];
$debug = $argv[2];
if ($step != 1) {
    $startTime = strtotime(date('Y-m-d', strtotime("-1 days"))) - 28800;
    $endTime = $startTime+86400;
}

// 纸质红包组id
$bonusGroupIdsConfig = BonusConfModel::get('CARD_BONUS_GROUP_IDS');
$bonusGroupIds = explode(',', $bonusGroupIdsConfig);
foreach ($bonusGroupIds as $key => $value) {
    if (!ctype_digit($value)) {
        $message = '错误的配置!bonusGroupIdsConfig:' .$bonusGroupIdsConfig;
        bonusLog($message);
        \libs\utils\Alarm::push('bonusCrontab', 'cardBonus', $message);
        exit;
    }
}
$bonusGroupIds = implode(',', $bonusGroupIds);

// 获取所有领取过物料二维码红包的用户
$sql = 'SELECT owner_uid, id FROM firstp2p_bonus WHERE group_id IN (' .$bonusGroupIds. ') AND owner_uid > 0 AND rebate_status = 0';
$result = $GLOBALS['db']->get_slave()->query($sql);
$cardUids = array();
$bonusUidMap = array();
$countCardUids = 0;
$rebateUsers = array();
while ($data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    ++$countCardUids;
    $cardUids[$data['id']] = $data['owner_uid'];
    $bonusUidMap[$data['owner_uid']] = $data['id'];
    if ($countCardUids%1000 == 0) {
        sendBonus($cardUids, $bonusUidMap, $step, $startTime, $endTime, $rebateUsers, $debug, $countCardUids);
        $bonusUidMap = array();
        $cardUids = array();
    }
}

if (!empty($cardUids)) {
    sendBonus($cardUids, $bonusUidMap, $step, $startTime, $endTime, $rebateUsers, $debug, $countCardUids);
}

// 发送红包逻辑
function sendBonus($cardUids, $bonusUidMap, $step = 0, $startTime = '', $endTime = '', &$rebateUsers, $debug = false, $countCardUids) {
    $cardUidStr = implode(',', $cardUids);
    // 获取领过红包并投过非新手标的用户
    $sql = 'SELECT dl.user_id FROM firstp2p_deal_load dl
            LEFT JOIN firstp2p_deal d ON dl.deal_id = d.id
            WHERE d.deal_crowd NOT IN (1,8) AND dl.user_id IN (' .$cardUidStr. ')';
    if ($step != 1) {
        $sql .= ' AND dl.create_time >=' .$startTime. ' AND dl.create_time < ' .$endTime;
    } else {
        $sql .= ' AND dl.create_time >= UNIX_TIMESTAMP("2015-01-19") - 28800';
    }
    if ($debug) {
        echo $sql . PHP_EOL;
    }

    $result = $GLOBALS['db']->get_slave()->query($sql);
    $dealUids = array();
    while ($data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
        $dealUids[$data['user_id']] = $data['user_id'];
    }

    if (empty($dealUids)) {
        $message = 'No match users! deal currentCount:' .$countCardUids . PHP_EOL;
        bonusLog($message);
        return false;
    }

    $uidStr = implode(',', $dealUids);
    // 获取这些用户的邀请码
    $sql = 'SELECT invite_code, id FROM firstp2p_user WHERE id in (' .$uidStr. ')';
    $result = $GLOBALS['db']->get_slave()->query($sql);
    $couponService = new \core\service\CouponService();
    while ($matchCardUser = $GLOBALS['db']->get_slave()->fetchRow($result)) {
        $bonusId = $bonusUidMap[$matchCardUser['id']];
        // 邀请码为空，更新状态，跳过
        if (!$matchCardUser['invite_code']) {
             if (!$debug) {
                 $res = $GLOBALS['db']->autoExecute('firstp2p_bonus', array('rebate_status' => BonusModel::CANNOT_REBATE), 'UPDATE', 'id='.$bonusId);
             }
             continue;
        }
        // 获取这些邀请码对应的用户
        if (empty($rebateUsers[$matchCardUser['invite_code']])) {
            $rebateUserId = $couponService::hexToUserId($matchCardUser['invite_code']);
            $user = \core\dao\UserModel::instance()->find($rebateUserId, 'id,mobile,coupon_level_id,is_delete,is_effect');
            if (empty($user) || $user['is_delete'] || empty($user['is_effect'])) {
                $message = "[ERROR]Can't find from rebate user cardUserId:{$matchCardUser['id']},invite_code:{$matchCardUser['invite_code']},rebateUserId:{$rebateUserId},bonusId:{$bonusId}" .PHP_EOL;
                bonusLog($message);
                if (!$debug) {
                    $res = $GLOBALS['db']->autoExecute('firstp2p_bonus', array('rebate_status' => BonusModel::CANNOT_REBATE), 'UPDATE', 'id='.$bonusId);
                }
                continue;
            }
            $rebateUsers[$matchCardUser['invite_code']] = $user;
        }
        $user = $rebateUsers[$matchCardUser['invite_code']];
        // 给用户发送红包
        $currentTime = time();
        $expiredTime = $currentTime + 86400;
        $GLOBALS['db']->startTrans();
        try {
            if (!$debug) {
                $res = $GLOBALS['db']->autoExecute('firstp2p_bonus', array('rebate_status' => BonusModel::REBATE_SUCCESS), 'UPDATE', 'id='.$bonusId);
                if (!$res || $GLOBALS['db']->affected_rows() != 1) {
                    throw new \Exception("更新bonus表出错cardUserId:{$matchCardUser['id']},rebateUserId:{$user['id']},bonusId:{$bonusId}");
                }
                $res = BonusModel::instance()->single_bonus(0, 0, $user['id'], $user['mobile'], 1, 10, $currentTime, $expiredTime, $openid);
                if (!$res) {
                    throw new \Exception("发放红包出错cardUserId:{$matchCardUser['id']},rebateUserId:{$user['id']},bonusId:{$bonusId}");
                }
            }
            $GLOBALS['db']->commit();
            $message = "[SUCCESS]cardUserId:{$matchCardUser['id']},rebateUserId:{$user['id']},bonusId:{$bonusId}" . PHP_EOL;
            bonusLog($message);
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $message ='[ERROR]' .$e->getMessage().PHP_EOL;
            bonusLog($message);
        }
    }
}

// 记录log
function bonusLog($message) {
    Logger::wLog($message, Logger::INFO, Logger::FILE, LOG_PATH ."bonus_crontab" . date('Ymd') .'.log');
}
