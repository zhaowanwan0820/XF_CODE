<?php
/**
 * 网信账户自动提现
 */
require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\PaymentApi;
use libs\utils\Alarm;
use libs\utils\Logger;
use core\service\UserCarryService;
use core\dao\ConfModel;
use core\dao\UserModel;

\libs\utils\Script::start();

ini_set('memory_limit', '2048M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $confModel = ConfModel::instance();
    //自动提现时间
    $withdrawTime = app_conf('AUTO_WITHDRAW_TIME');
    if (empty($withdrawTime)) {
        throw new \Exception('未配置自动提现时间');
    }

    //自动提现用户
    $userIds = app_conf('AUTO_WITHDRAW_USER_IDS');
    if (empty($userIds)) {
        throw new \Exception('未配置自动提现用户ID');
    }

    $now = date('H:i');
    if ($now !== $withdrawTime) {
        throw new \Exception('还没到自动提现时间, autoWithdrawTime: ' . $withdrawTime . ', now: ' . $now);
    }

    $userIdArr = explode(',', $userIds);
    $userCarryService = new UserCarryService();
    foreach ($userIdArr as $userId) {
        $userId = intval($userId);
        if (empty($userId)) {
            continue;
        }
        $ret = $userCarryService->withdrawBalance($userId);
    }
} catch (\Exception $e) {
    Logger::info('auto_withdraw. ' . $e->getMessage());
}

\libs\utils\Script::end();
