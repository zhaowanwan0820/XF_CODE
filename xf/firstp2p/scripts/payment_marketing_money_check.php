<?php
/**
 * 存管账户-余额监控相关：
 *     红包营销账户配置：BONUS_MARKETING_ACCOUNT_CONFIG
 *     红包营销账户余额不足报警短信：BONUS_MARKETING_MONITOR_MOBILES
 * 超级账户-余额监控相关：
 *     超级账户红包营销账户配置：BONUS_MARKETING_ACCOUNT_CONFIG_SUPER
 *     超级账户红包营销账户余额不足报警短信：BONUS_MARKETING_MONITOR_MOBILES_SUPER
 */
require_once(dirname(__FILE__) . '/../app/init.php');

use \libs\utils\Script;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\dao\UserModel;
use core\dao\UserThirdBalanceModel;
use core\service\SupervisionService;
use core\dao\SupervisionTransferModel;
use NCFGroup\Common\Library\Idworker;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
Script::start();

// 监控帐号、手机号的键值配置
$_MONITOR_CONFIG = [
    'SV_ACCOUNT' => 'BONUS_MARKETING_ACCOUNT_CONFIG', // 存管账户-红包营销账户配置
    'SV_MOBILE' => 'BONUS_MARKETING_MONITOR_MOBILES', // 存管账户-红包营销账户余额不足报警短信
    'SUPER_ACCOUNT' => 'BONUS_MARKETING_ACCOUNT_CONFIG_SUPER', // 超级账户-红包营销账户配置
    'SUPER_MOBILE' => 'BONUS_MARKETING_MONITOR_MOBILES_SUPER', // 超级账户-红包营销账户余额不足报警短信
];

function getMontitorConfig($config) {
    if (empty($config)) {
        return false;
    }

    $confKey = ['userId', 'moneyThreshold'];
    $mConfig = [];
    $config = explode('|', $config);
    foreach ($config as $confValue) {
        $confValue = explode(',', $confValue);
        $res = array_combine($confKey, $confValue);
        if (!$res) {
            PaymentApi::log('Marketing Money Check. Config Fail .' . $config);
            continue;
        }
        $mConfig[] = $res;
    }

    return $mConfig;
}

function sendAlarmSms($config, $mobileKey = 'BONUS_MARKETING_MONITOR_MOBILES', $msgFlag = '存管') {
    $msg = "您好，用户ID为{$config['userId']}的{$msgFlag}账户余额已低于预设阈值{$config['moneyThreshold']}元，请尽快完成充值。";
    $mobiles = app_conf($mobileKey);
    if (empty($mobiles)) {
        Logger::info('Marketing_Money_Check_Send_Sms_End. No Mobile Config');
        return true;
    }
    $mobiles = explode(',', $mobiles);
    //短信通知
    $ret = \libs\sms\SmsServer::sendAlertSms($mobiles, $msg);
    Logger::info('Marketing_Money_Check_Send_Sms_End. mobiles:' . json_encode($mobiles) . ', ret:' . $ret);
}

// 存管账户余额监控
$startTime = microtime(true);
Logger::info('Marketing_Money_Check_SV_Start.');
$config = getMontitorConfig(app_conf($_MONITOR_CONFIG['SV_ACCOUNT']));
if (empty($config)) {
    Logger::info('Marketing_Money_Check_SV_End. No Monitor Config');
    return;
}

foreach ($config as $accountConfig) {
    // 存管余额大于等于阈值，跳过
    $supervisionMoney = UserThirdBalanceModel::instance()->getUserSupervisionMoney($accountConfig['userId']);
    if (bccomp($supervisionMoney['supervisionBalance'], $accountConfig['moneyThreshold']) >= 0) {
        continue;
    }

    // 余额划转开关关掉直接报警
    if (app_conf('SV_UNTRANSFERABLE')) {
        Logger::info('Marketing_Money_Check_SV transfer close, userId:' . $accountConfig['userId']);
        sendAlarmSms($accountConfig, $_MONITOR_CONFIG['SV_MOBILE']);
        continue;
    }

    // 超级账户余额小于监控阈值直接报警
    $user = UserModel::instance()->find($accountConfig['userId'], 'money');
    if (bccomp($user['money'], $accountConfig['moneyThreshold']) < 0) {
        Logger::info('Marketing_Money_Check_SV Balance < moneyThreshold, userId:' . $accountConfig['userId']);
        sendAlarmSms($accountConfig, $_MONITOR_CONFIG['SV_MOBILE']);
        continue;
    }

    // 发起划转请求
    $orderId = Idworker::instance()->getId();
    $params = [
        'userId' => $accountConfig['userId'],
        'amount' => bcmul($accountConfig['moneyThreshold'], 100),
        'orderId' => $orderId,
        'currency' => 'CNY',
        'superUserId' => $accountConfig['userId'],
    ];
    $direction = SupervisionTransferModel::DIRECTION_TO_SUPERVISION;
    $supervisionService = new SupervisionService();
    $res = $supervisionService->requestSupervisionInterface($direction, $params);
    if (!$res) {
        Logger::info('Marketing_Money_Check_SV_Charge_Fail, userId:' . $accountConfig['userId']);
        sendAlarmSms($accountConfig, $_MONITOR_CONFIG['SV_MOBILE']);
    } else {
        Logger::info('Marketing_Money_Check_SV_Charge_Success, userId:' . $accountConfig['userId']);
    }
}
Logger::info('Marketing_Money_Check_SV_End. cost:' . round(microtime(true) - $startTime, 4) . 's');



//超级账户余额监控
$startTime = microtime(true);
$superAccountConfig = app_conf($_MONITOR_CONFIG['SUPER_ACCOUNT']);
Logger::info('Marketing_Money_Check_Super_Start. superAccountConfig:' . $superAccountConfig);
$configSuper = getMontitorConfig($superAccountConfig);
if (empty($configSuper)) {
    Logger::info('Marketing_Money_Check_Super_End. No Monitor Config');
    return;
}

foreach ($configSuper as $accountSuperConfig) {
    if ((int)$accountSuperConfig['userId'] <= 0 || !is_numeric($accountSuperConfig['moneyThreshold'])) {
        continue;
    }
    // 超级账户余额小于阈值，直接报警
    $user = UserModel::instance()->find((int)$accountSuperConfig['userId'], 'money');
    if (bccomp($user['money'], $accountSuperConfig['moneyThreshold'], 2) < 0) {
        Logger::info('Marketing_Money_Check_Super Balance < moneyThreshold, userId:' . $accountSuperConfig['userId'] . ', Balance:' . $user['money'], ', moneyThreshold:' . $accountSuperConfig['moneyThreshold']);
        sendAlarmSms($accountSuperConfig, $_MONITOR_CONFIG['SUPER_MOBILE'], '超级');
        continue;
    }
}
Logger::info('Marketing_Money_Check_Super_End. cost:' . round(microtime(true) - $startTime, 4) . 's');

Script::end();
