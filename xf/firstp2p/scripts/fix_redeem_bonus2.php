<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\service\BonusService;
use core\service\CouponService;
use core\dao\BonusConfModel;
use core\dao\UserModel;
use core\service\DealLoadService;
use core\dao\BonusModel;
use core\dao\DealLoadModel;
use libs\utils\Logger;

ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
$sql = "SELECT dl.id, dl.user_id FROM (SELECT min(dl.id) as tid FROM `firstp2p_deal_load` dl
LEFT JOIN (SELECT * FROM firstp2p_compound_redemption_apply WHERE create_time > UNIX_TIMESTAMP('2015-05-29 20:00:00') -28800 AND create_time <= UNIX_TIMESTAMP('2015-06-03 12:00:00') - 28800) AS cra on cra.deal_load_id = dl.id 
WHERE dl.create_time <= UNIX_TIMESTAMP('2015-06-03 12:00:00') - 28800 AND dl.create_time > UNIX_TIMESTAMP('2015-05-29 20:00:00') -28800 AND cra.deal_load_id IS NOT NULL
GROUP BY dl.user_id) AS tmp
LEFT JOIN firstp2p_deal_load dl ON dl.id = tmp.tid LIMIT 10000";
$result = $GLOBALS['db']->get_slave()->query($sql);
$successUsers = array();
while($result && $data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    $userId = $data['user_id'];
    $dealLoadId = $data['id'];
    $firstDeal = DealLoadModel::instance()->getFirstDealByUser($userId);
    if ($firstDeal['id'] != $dealLoadId) {
        bonusLog("[TIPS]不是首投");
        continue;
    }

    $bonusService = new BonusService();

    // 给邀请用户发红包
    if (!$firstDeal['short_alias']) {
        usleep(20);
        $message = '[TIPS]没有邀请人';
        bonusLog($message);
        continue;
    }
    if ($firstDeal['create_time'] < strtotime('2015-05-29 20:00:00') -28800) {
        usleep(20);
        $message = '[TIPS]不在补发范围内'. $firstDeal['id'];
        bonusLog($message);
        continue;
    }
    // 获取rule
    $rebateMoney = getRebateMoney($firstDeal['money']) - 20;
    if ($rebateMoney <= 0) {
        usleep(20);
        $message = '[TIPS]无对应返利规则'.$rebateMoney;
        bonusLog($message);
        continue;
    }
    $couponService = new CouponService();
    $coupon = $couponService->checkCoupon($firstDeal['short_alias']);
    if ($coupon !== FALSE) {
        $rebateUserId = $coupon['refer_user_id'];
    }
    if (!$rebateUserId) {
        usleep(20);
        $message = "[TIPS]邀请码无效，不返红包{$firstDeal['short_alias']}";
        bonusLog($message);
        continue;
    }

    // 自己的邀请码，不返红包
    if ($rebateUserId == $userId) {
        usleep(20);
        $message = "[TIPS]邀请人为本人，不返红包";
        bonusLog($message);
        continue;
    }

    $blackList = explode('|', BonusConfModel::get('BONUS_FOR_INVITE_BLACK_LIST'));
    // 判断用户是否在黑名单
    if (!empty($blackList) && in_array($rebateUserId, $blackList)) {
        usleep(20);
        $message = "[TIPS]无效的邀请人,用户在黑名单中";
        bonusLog($message);
        continue;
    }

    $inviteUser = UserModel::instance()->find($rebateUserId, 'id,user_name,real_name,mobile');
    $inviteUser = $inviteUser->getRow();

    // 用户组黑名单
    $groupRebateBlack = BonusConfModel::get('REBATE_GROUP_BLACK_LIST');
    if ($groupRebateBlack) {
        $groupRebateBlack = explode(',', $groupRebateBlack);
        if (!empty($groupRebateBlack) && in_array($inviteUser['group_id'], $groupRebateBlack)) {
            usleep(20);
            $message = "[TIPS]无效的邀请人,用户组在黑名单中";
            bonusLog($message);
            continue;
        }
    }

    // 判断邀请人是否投资过两笔
    $dealLoadService = new DealLoadService();
    $count = $dealLoadService->countByUserId($rebateUserId, false);
    if ($count < 2) {
        usleep(20);
        $message = "[TIPS]邀请人投资未满足返利条件";
        bonusLog($message);
        continue;
    }

    $currentTime = time();
    $expiredTime = $currentTime + 2 * 3600 * 24;
    $condition = 'type ='.BonusModel::BONUS_FIRST_DEAL_FOR_INVITE.' AND owner_uid=' .$inviteUser['id']. ' AND refer_mobile=' .$userId;
    $bonus = BonusModel::instance()->findBy($condition, 'id');
    if (isset($bonus['id']) && $bonus['money'] != 20) {
        usleep(20);
        $message = "[SUCCESS]重复执行";
        bonusLog($message);
    } else {
        $res = BonusModel::instance()->single_bonus(0, 0, $inviteUser['id'], $inviteUser['mobile'], 1, $rebateMoney, $currentTime, $expiredTime, NULL, $userId, BonusModel::BONUS_FIRST_DEAL_FOR_INVITE);
        $successUsers[$inviteUser['id']] = $inviteUser;
        $message = "[SUCCESS]$rebateMoney";
        bonusLog($message);
    }
}
// 输出手机号
foreach($successUsers as $user) {
    file_put_contents(dirname(__FILE__). "/../log/fix_redeem_mobile_" . to_date(time(), "Ymd") . ".log", $user['mobile'] . PHP_EOL, FILE_APPEND);
    file_put_contents(dirname(__FILE__). "/../log/fix_redeem_user_" . to_date(time(), "Ymd") . ".log", implode("\t", $user) . PHP_EOL, FILE_APPEND);
}

function bonusLog($message) {
    Logger::wLog($message . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."fix_redeem_bonus" . date('Ymd') .'.log');
}

function getRebateMoney($dealMoney) {
    $rebateMoney = 0;
    $rules = array(10000 => 100, 5000 => 80, 500 => 40, 100 => 20);
    foreach ($rules as $key => $money) {
        if (bccomp($dealMoney, $key) >= 0) {
            $rebateMoney = $money;
            return $rebateMoney;
        }
    }
    return $rebateMoney;
}

