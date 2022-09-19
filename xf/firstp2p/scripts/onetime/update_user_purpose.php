<?php
/**
 * 将混合用途的存管用户刷为借款户或投资户
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;
use \libs\utils\PaymentApi;
use \core\dao\EnterpriseModel;

$start = isset($argv[1]) ? intval($argv[1]) : 0;
$end = isset($argv[2]) ? intval($argv[2]) : 0;

if (empty($start) || empty($end)) {
    exit('input start and end');
}

for ($i = $start; $i <= $end; $i += 1000) {
    $s = microtime(true);
    $endId = min($end, $i + 1000);
    $users = Db::getInstance('firstp2p')->getAll("SELECT id FROM firstp2p_user WHERE user_purpose=0 AND supervision_user_id>0 AND id BETWEEN {$i} AND {$endId}");

    foreach ($users as $item) {
        $purpose = GetUserPurpose($item['id']);
        UpdateUserPurpose($item['id'], $purpose);
    }

    Logger::info(sprintf('update users done. cost:%ss, startId:%s, endId:%s, count:%s', round(microtime(true) - $s, 3), $i, $endId, count($users)));
}

/**
 * 获取用户类型
 */
function GetUserPurpose($userId) {
    $result = Db::getInstance('firstp2p')->getOne("SELECT * FROM firstp2p_deal WHERE user_id='{$userId}' LIMIT 1");

    if (empty($result)) {
        //投资户
        return core\dao\EnterpriseModel::COMPANY_PURPOSE_INVESTMENT;
    }

    //借款户
    return EnterpriseModel::COMPANY_PURPOSE_FINANCE;
}

/**
 * 更新用户类型
 */
function UpdateUserPurpose($userId, $purpose) {
    //支付端
    $params = array(
        'userId' => $userId,
        'bizType' => '0'.$purpose,
    );
    $result = PaymentApi::instance('supervision')->request('biztypeModify', $params);

    Logger::info("supervion biztypeModify request. userId:{$userId}, purpose:{$purpose}, result:".json_encode($result, JSON_UNESCAPED_UNICODE));

    //数据库
    $data = array(
        'user_purpose' => $purpose,
    );
    $result = Db::getInstance('firstp2p')->update('firstp2p_user', $data, "id='$userId'");

    Logger::info("update user purpose. userId:{$userId}, purpose:{$purpose}, result:{$result}");
}
