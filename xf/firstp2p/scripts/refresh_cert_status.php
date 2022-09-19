<?php
/**
 * 刷新用户绑卡认证类型
 * author: weiwei12@ucfgroup.com
 */

require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\PaymentApi;
use libs\utils\LOGGER;
use core\service\UserBankcardService;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

$cert_status_map = array(
    'EXTERNAL_CERT' => 1, //IVR语音认证
    'FASTPAY_CERT'  => 2, //快捷认证(四要素认证)
    'TRANSFER_CERT' => 3, //转账认证
    'WHITELIST_CERT' => 4, //白名单
    'REMIT_CERT'    => 5, //打款认证
    'ONLY_CARD'    => 6, //卡密认证
    'AUDIT_CERT'    => 7, //人工认证
    'NO_CERT'    => 8, //未认证
);

$start_id = 0;
$length = 1000;

$succeed_num = 0;

$max_sql = "SELECT MAX(`id`) FROM `firstp2p_user_bankcard`";
$max_id = \libs\db\Db::getInstance('firstp2p')->getOne($max_sql);
if (empty($max_id)) {
    PaymentApi::log("refresh cert status failed, get max id failed", LOGGER::WARN);
}

$userBankCardObj = new UserBankcardService();
while (true) {
    $end_id = $start_id + $length - 1;
    $select_sql = "SELECT `id`, `cert_status`, `user_id`, `bankcard` FROM `firstp2p_user_bankcard` WHERE `id` >= '{$start_id}' AND `id` <= '{$end_id}'";
    $result = \libs\db\Db::getInstance('firstp2p')->getAll($select_sql);
    foreach ($result as $ret) {
        if ($ret['cert_status'] != 0) {
            continue;
        }

        // 获取支付系统所有银行卡列表-安全卡数据
        $bank_info = $userBankCardObj->queryBankCardsList($ret['user_id']);
        if (empty($bank_info['list'])) {
            PaymentApi::log("refresh cert status failed, failed id:{$ret['id']}, bankCards is empty, user_id: {$ret['user_id']}", LOGGER::WARN);
            continue;
        }
        //查找bank card
        $bank_cards = $bank_info['list'];
        $card = array();
        foreach ($bank_cards as $bank_card) {
            if ($ret['bankcard'] == $bank_card['cardNo']) {
                $card = $bank_card;
                break;
            }
        }
        if (empty($card)) {
            $ret['bankcard'] = formatBankcard($ret['bankcard']); //日志脱敏
            PaymentApi::log("refresh cert status failed, failed id:{$ret['id']}, card not found, user_id: {$ret['user_id']}, ret: " . json_encode($ret), LOGGER::WARN);
            continue;
        }
        if (empty($card['certStatus'])) {
            $card['cardNo'] = formatBankcard($card['cardNo']); //日志脱敏
            PaymentApi::log("refresh cert status failed, failed id:{$ret['id']}, certStatus is empty, user_id: {$ret['user_id']}, card: " . json_encode($card), LOGGER::WARN);
            continue;
        }

        $cert_status = isset($cert_status_map[$card['certStatus']]) ? $cert_status_map[$card['certStatus']] : 0;
        $update_sql = "UPDATE `firstp2p_user_bankcard` set `cert_status` = '{$cert_status}' where `id` = '{$ret['id']}'";
        $update =  \libs\db\Db::getInstance('firstp2p')->query($update_sql);
        if (!$update) {
            PaymentApi::log("refresh cert status failed, update failed", LOGGER::WARN);
        }
    }

    if ($end_id >= $max_id) {
        break;
    }
    $start_id += $length;
}

exit(0);

