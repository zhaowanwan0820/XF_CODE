<?php
require(dirname(__FILE__) . '/../app/init.php');
require(dirname(__FILE__) . '/cashResendList.php');
use core\service\BonusService;

$userId = $argv[1];
if (!$userId) {
    die('Need UserId!');
}
if (empty($whiteList)) {
    die('Need User List');
}
$bonusService = new BonusService();
$startTime = strtotime('2015-04-10 19:00:00');
$endTime = strtotime('2015-04-10 22:30:00');
$sql = 'SELECT owner_uid,mobile FROM firstp2p_bonus WHERE type = 7 AND status = 2 AND created_at >='.$startTime
       .' AND created_at <= '.$endTime.' AND refer_mobile = "'.$userId.'"';
$result = $GLOBALS['db']->query($sql);
while($result && $data = $GLOBALS['db']->fetchRow($result)) {
    if (!isset($whiteList[$data['owner_uid']])) {
        continue;
    }
    try {
        $res = $bonusService->rebateCashBonus($data['owner_uid']);
        if ($res) {
            echo "SUCCESS\t{$data['owner_uid']}\t{$data['mobile']}" . PHP_EOL;
        } else {
            echo "FAIL\t{$data['owner_uid']}\t{$data['mobile']}" . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "FAIL\t{$data['owner_uid']}\t{$data['mobile']}\t".$e->getMessage() . PHP_EOL;
    }
}
