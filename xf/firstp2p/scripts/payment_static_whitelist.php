<?php
/**
 * 用户余额核对
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');
require_once dirname(__FILE__).'/../libs/common/functions.php';
FP::import("libs.common.dict");

use libs\utils\PaymentApi;
use core\service\UserTagService;

error_reporting(E_ALL);
ini_set('display_errors', 1);

\libs\utils\Script::start();

$userContents = file_get_contents('http://static.firstp2p.com/attachment/201704/26/11/a7e1cde482c892fd95f75f7d727da429/630e88ce8aba6c5c6f111c07cc97e9ce.csv');
$userIds = explode("\n", $userContents);
PaymentApi::log('StaticWhiteList start.');

$startTime = microtime(true);
foreach ($userIds as $userId)
{
    $s = microtime(true);
    if (empty($userId)) {
        continue;
    }
    // 打用户资料完善的tag
    $userTagService  = new UserTagService();
    $userTagService->addUserTagsByConstName($userId, 'SUPERVISION_STATIC_WHITELIST');
    PaymentApi::log(' tagUser addTag:SUPERVISION_STATIC_WHITELIST'.$userId);
}
PaymentApi::log('StaticWhiteList end, timeeclapsed:'.(microtime(true) - $s));

PaymentApi::log('Sync start');
// 同步银行卡验证状态
$db = \libs\db\Db::getInstance('firstp2p','master');
$userTagService = new UserTagService();
$tagUsers = $userTagService->getUidsByTagId(1124);
$update_time = time() - 28800;
foreach ($tagUsers as $userId) {
    $userId = intval($userId);
    $userInfo = $db->getRow("SELECT user_purpose,id,user_type,mobile,mobile_code FROM firstp2p_user WHERE id = '{$userId}'");
    if (empty($userInfo)) {
        continue;
    }
    $bankInfo = $db->getRow("SELECT bank_id,bankcard FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
    if (empty($bankInfo)) {
        continue;
    }
    $userInfo['bankcard'] = $bankInfo['bankcard'];
    $shortName = $db->getOne("SELECT short_name FROM firstp2p_bank WHERE id = '{$bankInfo['bank_id']}'");
    $params = [];
    $params['userId'] = $userId;
    $params['orderId'] = md5(time());
    $params['cardNo'] = $userInfo['bankcard'];
    $params['bankCode'] = $shortName;
    $result = PaymentApi::instance()->request('staticWhitelist', $params);
    if(isset($result['status']) && $result['status'] != '00') {
        PaymentApi::log('sync whitelist user:'.$userId.' fail.');
    }
    PaymentApi::log('sync whitelist user:'.$userId.' success.');
}
PaymentApi::log('Sync end');
