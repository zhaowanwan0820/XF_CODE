<?php
/**
 * 重置评估次数
 **/
require(dirname(__FILE__).'/../app/init.php');
use core\dao\UserRiskAssessmentLogModel;
use libs\utils\Logger;

$param = isset($argv[1]) ? $argv[1] : 0;

if (empty($param)) {
    exit ('缺少参数');
}

\libs\utils\Script::start();
$userIds = explode(',', $param);
foreach ($userIds as $userId) {
    $result = UserRiskAssessmentLogModel::instance()->removeUserLog($userId);
    if ($result) {
        \libs\utils\Script::log("reset_risk_num success, userId: {$userId}");
    }
}
\libs\utils\Script::end();

