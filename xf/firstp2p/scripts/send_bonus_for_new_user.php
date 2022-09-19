<?php
/**
 * JIRA : http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-1849
 * 新手标红包返利
 */
use \core\dao\UserModel;
use \core\service\BonusService;
use \libs\utils\Logger;
use \core\dao\BonusConfModel;
require(dirname(__FILE__) . '/../app/init.php');
ini_set('error_reporting', E_ERROR);

$dealId = intval($argv[1]);
$debug = $argv[2];
$startTime = strtotime(date('Y-m-d', strtotime("-1 days"))) - 28800;
$endTime = $startTime+86400;


// 发送红包逻辑
// 获取投过新手标的用户
$sql = 'SELECT dl.user_id, cl.refer_user_id FROM firstp2p_deal_load dl
        LEFT JOIN firstp2p_deal d ON dl.deal_id = d.id
        LEFT JOIN firstp2p_coupon_log cl ON cl.deal_load_id = dl.id
        WHERE d.deal_crowd in (1, 8) AND cl.id IS NOT NULL AND dl.deal_id > '.$dealId .'
        AND dl.create_time >=' .$startTime. ' AND dl.create_time < ' .$endTime;
if ($debug) {
    echo $sql . PHP_EOL;
}

$result = $GLOBALS['db']->get_slave()->query($sql);
$users = array();
$unEffectUsers = array();
$bonusService = new BonusService();
$newUserDealBonusMoney = BonusConfModel::get('NEW_USER_DEAL_BONUS_MONEY');
$newUserDealBonusNum = BonusConfModel::get('NEW_USER_DEAL_BONUS_NUM');
$newUserDealBonusLast = BonusConfModel::get('NEW_USER_DEAL_BONUS_LAST');
$newUserDealInviteBonusMoney = BonusConfModel::get('NEW_USER_DEAL_INVITE_BONUS_MONEY');
$newUserDealInviteBonusNum = BonusConfModel::get('NEW_USER_DEAL_INVITE_BONUS_NUM');
$newUserDealInviteBonusLast = BonusConfModel::get('NEW_USER_DEAL_INVITE_BONUS_LAST');
if (!$newUserDealBonusMoney || !$newUserDealBonusNum || !$newUserDealBonusLast
    || !$newUserDealInviteBonusMoney|| !$newUserDealInviteBonusNum || !$newUserDealInviteBonusLast) {
    echo "[ERROR]无效的配置项$newUserDealBonusMoney\t$newUserDealBonusNum\t$newUserDealBonusLast\t$newUserDealInviteBonusMoney\t$newUserDealInviteBonusNum\t$newUserDealInviteBonusLast" . PHP_EOL;
    bonusLog("[ERROR]无效的配置项$newUserDealBonusMoney\t$newUserDealBonusNum\t$newUserDealBonusLast\t$newUserDealInviteBonusMoney\t$newUserDealInviteBonusNum\t$newUserDealInviteBonusLast");
    exit;
}

if ($debug) {
    echo "$newUserDealBonusMoney\t$newUserDealBonusNum\t$newUserDealBonusLast\t$newUserDealInviteBonusMoney\t$newUserDealInviteBonusNum\t$newUserDealInviteBonusLast" . PHP_EOL;
    exit;
}


while ($data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    $rebateUser = true;
    $rebateReferUser = true;

    if (!$data['refer_user_id']) {
        $rebateReferUser = false;
    }

    // 当前用户
    if (in_array($data['user_id'], $unEffectUsers)) {
        $message = "[ERROR]无效的用户USER_ID.user_id:{$data['user_id']},refer_user_id:{$data['refer_user_id']}";
        bonusLog($message);
        $rebateUser = false;
    }

    if ($rebateUser) {
        $userInfo = \core\dao\UserModel::instance()->find($data['user_id'], 'id,coupon_level_id,is_delete,is_effect');
        if (empty($userInfo) || $userInfo['is_delete'] || empty($userInfo['is_effect'])) {
            $unEffectUsers[] = $data['user_id'];
            $message = "[ERROR]无效的用户USER_ID.user_id:{$data['user_id']},refer_user_id:{$data['refer_user_id']}";
            bonusLog($message);
            $rebateUser = false;
        }
        $users[$userInfo['id']] = $userInfo;
    }

    // 投资返利用户
    if ($rebateReferUser && in_array($data['refer_user_id'], $unEffectUsers)) {
        $message = "[ERROR]无效的用户REFER_USER_ID.user_id:{$data['user_id']},refer_user_id:{$data['refer_user_id']}";
        bonusLog($message);
        $rebateReferUser = false;
    }

    if ($rebateReferUser && empty($users[$data['refer_user_id']])) {
        $userInfo = \core\dao\UserModel::instance()->find($data['refer_user_id'], 'id,coupon_level_id,is_delete,is_effect');
        if (empty($userInfo) || $userInfo['is_delete'] || empty($userInfo['is_effect'])) {
            $unEffectUsers[] = $data['refer_user_id'];
            $message = "[ERROR]无效的用户REFER_USER_ID.user_id:{$data['user_id']},refer_user_id:{$data['refer_user_id']}";
            bonusLog($message);
            $rebateReferUser = false;
        }
        $users[$userInfo['id']] = $userInfo;
    }

    if ($rebateUser) {
        $res = $bonusService->generation($data['user_id'], 0, 0, 0.25, 0, $bonusService::TYPE_NEW_USER_DEAL, $newUserDealBonusMoney, $newUserDealBonusNum, $newUserDealBonusLast);
        if ($res) {
            bonusLog("[SUCCESS]成功发送红包USER|user_id:{$data['user_id']}");
        } else {
            bonusLog("[ERROR]失败发送红包USER|user_id:{$data['user_id']}");
        }
    }

    if ($rebateReferUser) {
        $res = $bonusService->generation($data['refer_user_id'], 0, 0, 0.25, 0, $bonusService::TYPE_NEW_USER_DEAL, $newUserDealInviteBonusMoney, $newUserDealInviteBonusNum, $newUserDealInviteBonusLast);
        if ($res) {
            bonusLog("[SUCCESS]成功发送红包组REFER_USER|user_id:{$data['refer_user_id']}");
        } else {
            bonusLog("[ERROR]失败发送红包REFER_USER|refer_user_id:{$data['refer_user_id']}");
        }
    }
}
// 记录log
function bonusLog($message) {
    Logger::wLog("NEW_USER_DEAL\t" .$message, Logger::INFO, Logger::FILE, LOG_PATH ."bonus_crontab" . date('Ymd') .'.log');
}
