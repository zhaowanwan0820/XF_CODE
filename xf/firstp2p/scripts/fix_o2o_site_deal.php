<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\service\BonusService;
use core\service\DealService;
use core\dao\DealLoadModel;
error_reporting(E_ERROR);
ini_set('display_errors', 1);
set_time_limit(0);
$bonusService = new BonusService();
$dealService = new DealService();
$bonusSql = "SELECT id,user_id,deal_id,money FROM firstp2p_deal_load WHERE site_id > 1 AND deal_type = 0 AND create_time >= UNIX_TIMESTAMP('2015-08-09 21:39') - 28800 AND create_time <= UNIX_TIMESTAMP('2015-08-10 00:33')-28800";
$firstBonusSql = "SELECT cra.deal_load_id,dl.user_id, dl.money, dl.short_alias FROM firstp2p_compound_redemption_apply cra
LEFT JOIN firstp2p_deal_load dl ON dl.id = cra.deal_load_id
WHERE dl.site_id > 1 AND cra.create_time >= UNIX_TIMESTAMP('2015-08-10 00:33') - 28800 AND cra.create_time <= UNIX_TIMESTAMP('2015-08-10 18:15:55') - 28800";
$result = $GLOBALS['db']->get_slave()->query($bonusSql);
while($result && $data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    // makeBonus($deal_id, $loan_id, $user_id, $loan_money)
    $res = $dealService->makeBonus($data['deal_id'], $data['id'], $data['user_id'], $data['money']);
    if ($res) {
        echo "BONUS:{$data['user_id']}" . PHP_EOL;
    }
}

$result = $GLOBALS['db']->get_slave()->query($firstBonusSql);
while($result && $data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    // firstDealRebate($userId, $inviteCode, $dealLoadId, $money, $redeemDeal = false)
    $sqlFirst = "SELECT id, deal_id, money FROM `firstp2p_deal_load` WHERE user_id = '{$data['user_id']}' ORDER BY id ASC LIMIT 1";
    $loadFirst = DealLoadModel::instance()->findBySql($sqlFirst, null, true);
    if ($loadFirst['id'] == $data['deal_load_id']) {
        $res = $bonusService->firstDealRebate($data['user_id'], $data['short_alias'], $data['deal_load_id'], $data['money'], false);
        if ($res) {
            echo "FIRST:{$data['user_id']}" . PHP_EOL;
        }
    }
}
