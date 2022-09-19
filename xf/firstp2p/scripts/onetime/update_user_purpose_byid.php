<?php
/**
 * 将混合用途的存管用户刷为借款户或投资户
 */
ini_set('memory_limit', '1024M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;
use \libs\utils\PaymentApi;

$filename = isset($argv[1]) ? $argv[1] : '';

if (empty($filename)) {
    exit('filename empty');
}

$contents = file($filename);
$contentsChunk = array_chunk($contents, 1000);

Logger::info("update user purpose start. file:{$filename}, sum:".count($contents).", count:".count($contentsChunk));

foreach ($contentsChunk as $item) {
    $s = microtime(true);

    $ids = preg_replace('/\s/', '', implode(",", $item));
    $users = Db::getInstance('firstp2p')->getAll("SELECT id, user_purpose FROM firstp2p_user WHERE id IN ({$ids})");

    foreach ($users as $user) {
        if ($user['user_purpose'] != 1 && $user['user_purpose'] != 2) {
            Logger::info("update users purpose failed. id:{$user['id']}, purpose:{$user['user_purpose']}");
            continue;
        }
        UpdateUserPurpose($user['id'], $user['user_purpose']);
    }

    Logger::info(sprintf('update users done. cost:%ss, count:%s', round(microtime(true) - $s, 3), count($users)));
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
}
