<?php
require(dirname(__FILE__) . '/../app/init.php');
use core\dao\SupervisionTransferModel;
use core\dao\UserModel;
use core\service\SupervisionFinanceService;
use libs\utils\Logger;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

Logger::info('start fix transfer data');

$supervisionTransferModel = new SupervisionTransferModel();
$userModel = new UserModel();
$db = \libs\db\Db::getInstance('firstp2p', 'master');
$source = <<<SOURCE
2017-06-13 09:10:08,superRecharge,59207659085107609,11225,100000
2017-06-12 04:13:51,superRecharge,58951913407779215,8198979,11000
SOURCE;

$arr = explode("\n", $source);
foreach ($arr as $val) {
    $data = explode(',', $val);
    if (count($data) != 5) {
        continue;
    }
    $orderId = intval($data[2]);
    $date = $data[0];
    $time = strtotime($date);
    $type = trim($data[1]);
    $userId = intval($data[3]);
    $amount = intval($data[4]);
    $amountYuan = bcdiv($amount, 100, 2);
    $orderInfo = $supervisionTransferModel->getTransferRecordByOutId($orderId);
    $userInfo = $userModel->find($userId);
    if (empty($orderInfo) && $userInfo && $type == 'superRecharge') {
        if (bccomp($userInfo['money'], $amountYuan, 2) == -1) {
            Logger::info(sprintf('user balance not enough, orderId: %s, userId %s, amount %s', $orderId, $userId, $amount));
            continue;
        }
        Logger::info(sprintf('fix data, orderId: %s, userId %s, amount %s', $orderId, $userId, $amount));
        try {
            $db->startTrans();
            $sql1 = sprintf('INSERT INTO firstp2p_jobs (function, params, create_time, start_time, retry_cnt, priority) VALUES (\'\\\\core\\\\service\\\\SupervisionOrderService::addSupervisionOrder\', \'[\"superRecharge\",{\"orderId\":%s,\"userId\":\"%s\",\"amount\":\"%s\",\"currency\":\"CNY\"}]\', \'%s\', \'\', \'3\', \'0\')', $orderId, $userId, $amount, $time - 28800);
            $db->query($sql1);

            $sql2 = sprintf('INSERT INTO firstp2p_supervision_transfer (direction, user_id, amount, transfer_status, create_time, out_order_id) VALUES (\'1\', \'%s\', \'%s\', \'0\', \'%s\', \'%s\')', $userId, $amount, $time, $orderId);
            $db->query($sql2);

            $sql3 = sprintf('UPDATE firstp2p_user SET `money`=`money`-\'%s\', `lock_money`=`lock_money`+\'%s\',update_time = \'%s\' WHERE `id`=\'%s\'  AND money >= %s', $amountYuan, $amountYuan, $time - 28800, $userId, $amountYuan);
            $db->query($sql3);
            if ($db->affected_rows() <= 0) {
                throw new \Exception('freeze user money fail');
            }

            $param = array(':user_id' => $userId);
            $tableName = \core\dao\UserLogModel::instance()->tableName(true, false, $param);
            $sql4 = sprintf('INSERT INTO %s (log_info, log_time, log_admin_id, log_user_id, money, lock_money, remaining_money, user_id, deal_type, note, remaining_total_money) VALUES (\'余额划转申请\', \'%s\', \'0\', \'%s\', \'-%s\', \'%s\', \'%s\', \'%s\', \'0\', \'网信理财账户余额划转到网贷P2P账户余额\', \'%s\')', $tableName, $time - 28800, $userId, $amountYuan, $amountYuan, $userInfo['money'], $userId, bcadd($amountYuan, $userInfo['money'], 2));
            $db->query($sql4);

            $db->commit();
            Logger::info(sprintf('fix data success, orderId: %s, userId %s, amount %s', $orderId, $userId, $amount));
        } catch (\Exception $e) {
            Logger::info(sprintf('fix data fail, orderId: %s, userId %s, amount %s, err: %s', $orderId, $userId, $amount, $e->getMessage()));
            $db->rollback();
        }
    }
}
Logger::info('end fix transfer data');


