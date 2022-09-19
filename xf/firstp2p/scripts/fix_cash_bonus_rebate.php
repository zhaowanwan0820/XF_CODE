<?php
/**
 * 修复新手标引起的现金红包返利问题
 * date:2015-06-29
 *
 */
require(dirname(__FILE__) . '/../app/init.php');
use core\service\BonusService;
use core\dao\BonusModel;
use core\dao\UserModel;

$bonusService = new BonusService();
$endTime = strtotime('2015-06-29 18:01:16') - 28800;
$sql = "SELECT id FROM firstp2p_deal WHERE id >= 32700 AND id <= 32724 AND deal_crowd = 1";
$dealRes = $GLOBALS['db']->get_slave()->query($sql);
while($dealRes && $dealInfo = $GLOBALS['db']->get_slave()->fetchRow($dealRes)) {
    $dealId = $dealInfo['id'];
    $sql = "SELECT user_id FROM firstp2p_deal_load WHERE deal_id = $dealId AND create_time <= $endTime";
    $dealLoadRes = $GLOBALS['db']->get_slave()->query($sql);
    while($dealLoadRes && $dealLoadInfo = $GLOBALS['db']->get_slave()->fetchRow($dealLoadRes)) {
        $mobile = rebateCashBonus($dealLoadInfo['user_id']);
        if ($mobile) {
            echo 'SUCCESS:' . $dealLoadInfo['user_id'] . ':' .$mobile. PHP_EOL;
        } else {
            echo 'FAILED:' . $dealLoadInfo['user_id'] . PHP_EOL;
        }
    }
}

function rebateCashBonus($userId, $ruleName = 'CASH_BONUS_RULE') {

    $bonusService = new BonusService();
    $condition = ' type = '.BonusModel::BONUS_CASH_NORMAL_FOR_NEW.' AND owner_uid = '. $userId. ' AND status = 2';
    $bonus = BonusModel::instance()->findBy($condition, 'id, money, created_at, expired_at, refer_mobile, mobile');
    if (empty($bonus)) {
        \libs\utils\PaymentApi::log("FirstDealCashRebate\tNO BONUS\t$userId");
        return true;
    }

    $inviteUserId = $bonus['refer_mobile'];
    $referUserMobile = $bonus['mobile'];

    $condition = ' type = ' .BonusModel::BONUS_CASH_FOR_INVITE. ' AND owner_uid= '.$inviteUserId. ' AND refer_mobile = ' .$referUserMobile;
    $bonus = BonusModel::instance()->findBy($condition, 'id, money, created_at, expired_at, refer_mobile, mobile');
    if (!empty($bonus)) {
        \libs\utils\PaymentApi::log("FirstDealCashRebate\tALREADY REBATE\t$userId\t$inviteUserId");
        return true;
    }

    $user = UserModel::instance()->find($inviteUserId, 'id,mobile,coupon_level_id,is_delete,is_effect', true);
    if (empty($user) || $user['is_delete'] || empty($user['is_effect'])) {
        \libs\utils\PaymentApi::log("FirstDealCashRebate\tINVITE USER UNEFFECT\t$userId\t$inviteUserId");
        return true;
    }

    if (!$bonusService->isCashBonusSender($inviteUserId)) {
        \libs\utils\PaymentApi::log("FirstDealCashRebate\tINVITE USER NOT FIT\t$userId\t$inviteUserId");
        return true;
    }

    $rebateRule = $bonusService->getBonusNewUserRebate($ruleName);
    if (empty($rebateRule)) {
        return true;
    }
    $bonusService->batch_id = $rebateRule['id'];
    if ($rebateRule['forInvite']['is_group'] == 1) {
        $groupType = self::TYPE_CASH_FOR_INVITE;
        $res = $bonusService->generation($user['id'], 0, 0, 0.25, 0, $groupType, $rebateRule['forInvite']['money'], $rebateRule['forInvite']['count'], $rebateRule['forInvite']['send_limit_day']);
    } else {
        $currentTime = time();
        $expiredTime = $currentTime + $rebateRule['forInvite']['use_limit_day'] * 3600 * 24;
        $bonusType = BonusModel::BONUS_CASH_FOR_INVITE;
        $res = BonusModel::instance()->single_bonus(0, 0, $user['id'], $user['mobile'], 1, $rebateRule['forInvite']['money'], $currentTime, $expiredTime, NULL, $referUserMobile, $bonusType);
        $res = true;
    }

    if ($res) {
        return $user['mobile'];
    } else {
        return false;
    }

}
