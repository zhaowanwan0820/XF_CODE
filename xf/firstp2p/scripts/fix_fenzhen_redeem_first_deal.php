<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\service\BonusService;
use core\service\CouponService;
use core\dao\DealLoadModel;
use core\dao\BonusModel;
use libs\utils\Logger;

ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
$bonusService = new BonusService();
$timeStart = isset($argv[1]) ? $argv[1] : '';
$timeEnd = isset($argv[2]) ? $argv[2] : '';
if (!$timeStart || !$timeEnd) {
    die('请选择时间段!' . PHP_EOL);
}

if (date("Y-m-d", strtotime($timeStart)) != $timeStart || date("Y-m-d", strtotime($timeEnd)) != $timeEnd) {
    die('时间格式不正确，请填写日期!' . PHP_EOL);
}
$sql = "
SELECT dl.id, dl.user_id, dl.short_alias,dl.money, cra.create_time FROM firstp2p_compound_redemption_apply  cra
LEFT JOIN firstp2p_deal_load dl ON cra.deal_load_id = dl.id
WHERE cra.create_time > UNIX_TIMESTAMP('$timeStart') - 28800 AND cra.create_time <= UNIX_TIMESTAMP('$timeEnd') - 28800 AND dl.id IS NOT NULL
";
echo $sql . PHP_EOL;
$result = $GLOBALS['db']->get_slave()->query($sql);
while($result && $data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    $dataMessage = implode('|', $data);
    $firstDeal = DealLoadModel::instance()->getFirstDealByUser($data['user_id']);
    if ($firstDeal['id'] !== $data['id']) {
        echo '不是首投|'.$dataMessage.PHP_EOL;
        continue;
    }
    // 验证是否发过红包
    $sql = "SELECT COUNT(*) AS count FROM firstp2p_bonus WHERE owner_uid = {$data['user_id']} AND type IN (".BonusModel::BONUS_TASK . "," .BonusModel::BONUS_FIRST_DEAL_FOR_DEAL .")";
    echo $sql . PHP_EOL;
    $resendRes = $GLOBALS['db']->get_slave()->getRow($sql);
    if ($resendRes['count'] > 0 ) {
        echo '已发红包|'.$dataMessage.PHP_EOL;
        continue;
    }

    // 补发
    $taskId = $bonusService->firstDealRebate($data['user_id'], $data['short_alias'], $data['id'], $data['money'], false, $data['create_time']+28800);
    if (!is_bool($taskId)) {
        echo "补发成功|" . $dataMessage . PHP_EOL;
    } else {
        echo "邀请人在黑名单|" . $dataMessage . PHP_EOL;
    }
}
