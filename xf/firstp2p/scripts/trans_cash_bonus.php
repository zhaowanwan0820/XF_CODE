<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\service\BonusService;
use core\dao\BonusModel;
$userId = $argv[1];
if (!$userId) {
    die('需要userId');
}

$bonusService = new BonusService();

try {
    $condition = ' type IN (' . BonusModel::BONUS_CASH_FOR_NEW .', '.BonusModel::BONUS_CASH_NORMAL_FOR_NEW.') AND owner_uid = '. $userId. ' AND status = 1 AND expired_at >' . time();
    $bonus = BonusModel::instance()->findBy($condition, 'id, money, created_at, expired_at, refer_mobile, mobile, type');
    if (empty($bonus)) {
        echo 'NO BONUS' .PHP_EOL;
        exit;
    }
    if ($bonus['type'] == BonusModel::BONUS_CASH_NORMAL_FOR_NEW) {
        $updateRes = BonusModel::instance()->updateAll(array('type' => BonusModel::BONUS_CASH_FOR_NEW), 'id = ' .$bonus['id'], true);
    }
    $res = $bonusService->transCashBonus($userId);
    if ($res) {
        echo 'SUCCESS:' . $userId . PHP_EOL;
    }
} catch (\Exception $e) {
    die('FAILED:' .$userId . $e->getMessage());
}
