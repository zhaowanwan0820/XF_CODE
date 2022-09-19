<?php
/**
 * 用户余额核对
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');

use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\dao\UserBankcardModel;
use core\service\UserTagService;

error_reporting(E_ALL);
ini_set('display_errors', 1);

$args = $_SERVER['argv'];
if (!isset($args[1]) || !is_numeric($args[1])) {
    exit('Usage: /bin/to/php payment_bankcert_refresh.php [0-5]'.PHP_EOL);
}
if ($args[1] > 5 || $args[1] < 0) {
    exit('Usage: /bin/to/php payment_bankcert_refresh.php [0-5]'.PHP_EOL);
}

\libs\utils\Script::start();
$startTime = microtime(true);
PaymentApi::log('Bankcert refresh. group:'.$args[1]);

$db = \libs\db\Db::getInstance('firstp2p', 'master');

$sql = 'SELECT max(id) AS maxid FROM firstp2p_user_bankcard';
$maxUserId = $db->getOne($sql);

$startUserId = $args[1]*1000000;
$endUserId = ($args[1] + 1) * 1000000;

// 计算最大边界值
$maxUserId = min($endUserId, $maxUserId);

$userBankCardObj = new \core\service\UserBankcardService();

function getUcfpayUserinfo($p2pUserInfo) {
    $ucfpayResult = [];
    foreach ($p2pUserInfo as $info) {
        $params = ['userId' => $info['user_id']];
        $ucfpayInfo = [];
        // 获取支付系统所有银行卡列表
        $cardResult = $userBankCardObj->queryBankCardsList($params['userId'], true);
        if ($cardResult['respCode'] == '00' && !empty($cardResult['list'])) {
            $ucfpayInfo['userId'] = $info['user_id'];
            $ucfpayInfo['bankcardNo'] = $cardResult['list']['cardNo'];
            $ucfpayInfo['bankCode'] = $cardResult['list']['bankCode'];
            $ucfpayInfo['certStatus'] = $cardResult['list']['certStatus'];
        }
        $ucfpayResult[$info['user_id']] = $ucfpayInfo;
    }
    return $ucfpayResult;
}

for ($i = $startUserId; $i <= $maxUserId; $i += 1000)
{
    $s = microtime(true);
    $sql ="SELECT id,user_id,cert_status,verify_status FROM firstp2p_user_bankcard WHERE id BETWEEN {$i} AND {$i}+1000";
    $p2pUserInfo = $db->getAll($sql);
    if (empty($p2pUserInfo)) {
        continue;
    }
    $ucfpayUserInfo = getUcfpayUserinfo($p2pUserInfo);
    foreach ($p2pUserInfo as $userInfo)
    {

        try {
            $db->startTrans();
            $userId = $userInfo['user_id'];
            $data = [];
            $ucfUserInfo = isset($ucfpayUserInfo[$userId]) ? $ucfpayUserInfo[$userId] : null;
            if (!empty($ucfUserInfo) && !empty($ucfUserInfo['certStatus']) && $ucfUserInfo['certStatus'] != 'NO_CERT') {
                $data['cert_status'] = UserBankcardModel::$cert_status_map[$ucfUserInfo['certStatus']];
                $data['verify_status'] = 1;
                // 添加预开户用户标示
                $tagService = new UserTagService();
                $tagService->addUserTagsByConstName($userId, 'SV_UPGRADE_USER');
                }
            else {
                // 解除已验卡状态
                $data['verify_status'] = 0;
                \libs\utils\PaymentApi::log('certStatus sync '.$userId .' fail, ucfpay status not exsits.');
            }
            $db->autoExecute('firstp2p_user_bankcard', $data, 'UPDATE', ' user_id = '.$userId);
            $db->commit();
            \libs\utils\PaymentApi::log('certStatus sync '.$userId .' success');
        } catch (\Exception $e) {
            $db->rollback();
            \libs\utils\PaymentApi::log('certStatus sync '.$userId .' fail, '.$e->getMessage().'.');
        }
    }
    PaymentApi::log('certStatus timeeclapsed:'.(microtime(true) - $s));
}
