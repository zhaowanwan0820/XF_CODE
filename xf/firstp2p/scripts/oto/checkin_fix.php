<?php

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\O2OService;
use libs\utils\Logger;

set_time_limit(0);

echo date('Y-m-d H:i:s').'每日签到数据修复脚本开始执行：', PHP_EOL;

$list = require __DIR__.'/checkin_fix_user.php';

//定向用户组发红包
$o2oService = new O2OService();
$num_success = 0;

foreach ($list as $key=>$item){
    // 券id
    $couponGroupId = $item[0];
    // 用户id
    $userId = $item[1];
    // 伪造的交易id
    $dealLoadId = $item[2];

    $logInfo = '['.$key.']couponGroupId: '.$couponGroupId.', userId: '.$userId.', dealLoadId: '.$dealLoadId;
    echo $logInfo;
    Logger::info($logInfo);

    try {
        $res = $o2oService->sendAward($couponGroupId, $userId, $dealLoadId, 122);
        if ($res !== false) {
            echo ', success', PHP_EOL;
            $num_success++;
        } else {
            echo ', failed, msg: ', $o2oService->getErrorMsg(), PHP_EOL;
        }
    } catch (\Exception $ex) {
        echo 'failed, msg: ', $ex->getMessage(), PHP_EOL;
    }
}

$endStr = '共'.count($list).'个用户，数据修复成功'.$num_success."个";
echo $endStr.PHP_EOL;
Logger::info($endStr);