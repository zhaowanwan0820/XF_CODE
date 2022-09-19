<?php
/**
 * 导出所有的白名单用户 (生成日志，再从日志导出)
 */
require dirname(__FILE__).'/../app/init.php';

ini_set('memory_limit', '1024M');
set_time_limit(0);

use core\service\UserCreditService;
use libs\db\Db;
use libs\utils\Logger;
use libs\utils\PaymentApi;

$creditService = new UserCreditService();

$maxUserId = Db::getInstance('firstp2p', 'slave')->getOne('SELECT max(id) FROM firstp2p_user');

$start = isset($argv[1]) ? intval($argv[1]) : 1;
$end = isset($argv[2]) ? intval($argv[2]) : $maxUserId;

function __logger($content) {
    $content = 'WhiteUserExport. '.$content;
    Logger::info($content);
    PaymentApi::log($content);
}

__logger("maxUserId:{$maxUserId}, start:{$start}, end:{$end}");

for ($i = $start; $i < $end; $i++) {
    //ID，是否绑卡，验卡方式，是否投资，在投金额，冻结金额，可用余额，注册时间，最后资金操作时间，最后登陆时间，邀请人，白名单加入方式
    $isCredible = $creditService->isCredible($i);

    $result = array();
    if (!$isCredible) {
        continue;
    }

    $db = Db::getInstance('firstp2p', 'slave');

    $result['id'] = $i;

    $ret = $db->getRow("SELECT money, lock_money, refer_user_id, group_id, create_time FROM firstp2p_user WHERE id='{$i}'");
    $result['money'] = $ret['money'];
    $result['lock_money'] = $ret['lock_money'];
    $result['refer_user_id'] = $ret['refer_user_id'];
    $result['group_id'] = $ret['group_id'];
    $result['create_time'] = $ret['create_time'];

    $ret = $db->getRow("SELECT id, verify_status, cert_status FROM firstp2p_user_bankcard WHERE user_id='{$i}'");
    $result['banklistid'] = $ret['id'];
    $result['verify_status'] = $ret['verify_status'];
    $result['cert_status'] = $ret['cert_status'];

    $result['hasdeal'] = $db->getOne("SELECT id FROM firstp2p_deal_load WHERE user_id='{$i}' LIMIT 1");

    $ret = $db->getRow("SELECT norepay_principal,norepay_interest FROM `firstp2p_user_loan_repay_statistics` where user_id='{$i}' LIMIT 1");
    $result['norepay_principal'] = $ret['norepay_principal'];
    $result['norepay_interest'] = $ret['norepay_interest'];

    $result['lastLoginTime'] = get_user_last_time($i);

    __logger("WhiteUserExport. userId:{$i}, result:".json_encode($result));
    __logger("WhiteUserExport. userId:{$i}, csvresult:".implode(',', $result));
}

__logger("userId:{$i}, result:".json_encode($result));
