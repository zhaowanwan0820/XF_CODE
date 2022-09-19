<?php
// 随鑫约异常记录解锁
require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\LOGGER;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

$ids = isset($argv[1]) ? $argv[1] : '1092052,1092426,1092570,1092575,1105879,1105919,1105944,1105956,1106005,1106037,1106043,1106048,1106051,1106060,1106097,1106110,1106192,1106208,1106620,1106632,1106723,1106793,1106836,1109086,1109093,1125554,1125786,1126031,1126032,1149649,1149910,1150037,1150284,1162389,1162997,1173933,1173947,1174029,1178849,1178991,1179010,1179516,1179609,1179742,1179909,1180647,1218974';

$idArray = explode(',', $ids);
Logger::info('begin reserve unlock, ids:' . $ids);

$db = \libs\db\Db::getInstance('firstp2p');
foreach ($idArray as $id) {
    $id = intval($id);
    $updateSql = "UPDATE `firstp2p_user_reservation` SET `proc_status` = '0',`proc_id`='0' WHERE id={$id} AND proc_status = 1 AND end_time < " . time();
    $updateRes =  $db->query($updateSql);
    if (!$updateRes) {
        Logger::info('reserve unlock fail, id:' . $id);
    }
}
Logger::info('end reserve unlock');
