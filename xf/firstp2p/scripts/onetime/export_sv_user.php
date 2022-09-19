<?php
/**
 * 导出用户清单
 */
ini_set('memory_limit', '4096M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use libs\Db\Db;
use libs\utils\Logger;
use core\service\SupervisionAccountService;

$minId = isset($argv[1]) ? intval($argv[1]) : 0;
$maxId = isset($argv[2]) ? intval($argv[2]) : 100;
Logger::info(sprintf('begin export_sv_user, minId: %d, maxId: %d', $minId, $maxId));
$supervisionAccountObj = new SupervisionAccountService();

$sql = "SELECT id FROM `firstp2p_user` where id >= {$minId} AND  id < {$maxId}";
$userList = Db::getInstance('firstp2p')->getAll($sql);
foreach ($userList as $user) {
    $userId = $user['id'];
    $result = $supervisionAccountObj->memberSearch($userId);
    if ($result['respCode'] == '00') {
        Logger::info('export_sv_user success, result: ' . $userId . ',' . $result['data']['status']);
    } else {
        Logger::error(sprintf('export_sv_user error, userId: %d, result: %s', $userId, json_encode($result)));
    }
}
Logger::info(sprintf('end export_sv_user, minId: %d, maxId: %d', $minId, $maxId));


