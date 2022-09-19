<?php
/**
 * 发送红包组给指定用户
 */
require_once dirname(__FILE__).'/../../app/init.php';
use core\dao\BonusModel;
use core\dao\OtoBonusAccountModel;
use core\dao\OtoConfirmLogModel;

set_time_limit(0);

echo date('Y-m-d H:i:s').'发送分享红包到指定用户脚本开始执行：', PHP_EOL;

$list = require __DIR__.'/bonus_send_user.php';

//定向用户组发红包
$bonusService = new \core\service\BonusService();
$num_success = 0;
$payoutUserId = 6142068;
$bonusMode = OtoBonusAccountModel::MODE_CONFIRM;
$bonusType = BonusModel::BONUS_O2O_CONFIRMED_REBATE;
// 红包期限
$limit = 86400;
foreach ($list as $item){
    // 券id
    $couponId = $item[0];
    // 用户id
    $payinUserId = $item[1];
    // 分享红包金额
    $money = $item[2];
    // 分享红包个数
    $count = $item[3];
    echo 'couponId: ', $couponId, ', userId: ', $payinUserId, ', money: ', $money, ', count: ', $count, ', limit: ', $limit;
    $logInfo = OtoConfirmLogModel::instance()->getConfirmLogByGiftId($couponId);
    if (!empty($logInfo['id'])) {
        echo ', logId: ', $logInfo['id'];
    }

    // 补贴分享红包
    $bonusAccountInfo = array(
        'account_id' => $payoutUserId,
        'trigger_mode' => $bonusMode,
        'log_id' => $logInfo['id'],
    );

    try {
        $bonusService->generateO2OBonus($payinUserId, $money, $count, $limit, $bonusAccountInfo,
                $couponId, $bonusType);

        echo ', success', PHP_EOL;
        $num_success++;
    } catch (\Exception $ex) {
        echo 'failed, msg: ', $ex->getMessage(), PHP_EOL;
    }
}

echo '共',count($list),'个用户，红包发送成功', $num_success,"个\n";
