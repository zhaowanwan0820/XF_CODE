<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\service\O2OService;
use core\dao\OtoAcquireLogModel;
use core\service\BonusService;
use libs\utils\PaymentApi;
use core\dao\BonusModel;
use core\dao\DealLoadModel;
use libs\utils\Logger;
use core\dao\UserModel;
error_reporting(E_ERROR);
ini_set('display_errors', 1);

function getBonusSend($money) {
    $bonusMap = array(
        10000 => 150,
        5000 => 100,
        500 => 60,
        100 => 30
    );
    krsort($bonusMap);
    foreach ($bonusMap as $key => $value) {
        if ($money >= $key) {
            return $value;
        }
    }
    return false;
}

$sql = "SELECT oal.id, oal.user_id, oal.trigger_mode, oal.gift_group_id, oal.gift_id FROM firstp2p_oto_acquire_log oal
LEFT JOIN firstp2p_user u on u.id = oal.user_id
WHERE trigger_mode < 4 AND gift_id = 0 AND request_status = 1 AND oal.create_time > UNIX_TIMESTAMP('2015-11-17') AND oal.create_time < UNIX_TIMESTAMP('2015-11-21') AND u.refer_user_id = 0";
//$sql = "SELECT * FROM firstp2p_oto_acquire_log WHERE request_status = 1 ORDER BY id DESC LIMIT 0, 10";
// 5分钟跑一次够了吧
$result = $GLOBALS['db']->query($sql);
while($result && $data = $GLOBALS['db']->fetchRow($result)) {

    $logKey = 'FIX_OTO_ACQUIRE|'. $data['user_id'] . '|' . $data['trigger_mode'] . '|' . $data['id']  . '|';
    $firstDeal = DealLoadModel::instance()->getFirstDealByUser($data['user_id']);
    $user = UserModel::instance()->find($data['user_id'], 'id, mobile');
    $GLOBALS['db']->startTrans();
    try {
        $res = $GLOBALS['db']->query('UPDATE firstp2p_oto_acquire_log SET request_status = ' .OtoAcquireLogModel::REQUEST_STATUS_EMPTY. ' WHERE id = ' . $data['id']);
        if (!$res || $GLOBALS['db']->affected_rows() != 1) {
            throw new \Exception('该券无法更新');
        }

        if ($data['trigger_mode'] < 3) {
            PaymentApi::log($logKey. 'SUCCESS', Logger::INFO);
            $GLOBALS['db']->commit();
            continue;
        }

        $currentTime = time();
        $expiredTime = $currentTime + 30 * 3600 * 24;
        $bonusType = BonusModel::BONUS_FIRST_DEAL_FOR_DEAL;
        $condition = 'type=' .$bonusType.' AND owner_uid=' .$user['id'];
        $bonusMoney = getBonusSend($firstDeal['money']);
        if ($bonusMoney) {
            $bonus = BonusModel::instance()->findBy($condition, 'id');
            if (!isset($bonus['id'])) {
                $res = BonusModel::instance()->single_bonus(0, 0, $user['id'], $user['mobile'], 1, $bonusMoney, $currentTime, $expiredTime, NULL, NULL, $bonusType);
                if (!$res) {
                    throw new \Exception('红包发送失败');
                }
            }
        }
        $GLOBALS['db']->commit();
        PaymentApi::log($logKey. 'SUCCESS|'. $bonusMoney, Logger::INFO);
    } catch (\Exception $e) {
        $GLOBALS['db']->rollback();
        PaymentApi::log($logKey . 'FAIL|' .$e->getMessage(), Logger::INFO);
    }
}
