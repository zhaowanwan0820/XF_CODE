<?php
/**
 * 身份证重新验证
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');

use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\db\Db;

function __log__($content) {
    PaymentApi::log('[IDNO_RECHECK] '.$content);
    Logger::info('[IDNO_RECHECK] '.$content);
    echo '[IDNO_RECHECK] '.$content."\n";
}

$minId = isset($argv[1]) ? intval($argv[1]) : 7407555;
$maxId = isset($argv[2]) ? intval($argv[2]) : 7411321;
__log__("minId:{$minId}, maxId:{$maxId}");

for ($i = $minId; $i <= $maxId; $i++)
{
    $id = $i;
    $user = Db::getInstance('firstp2p', 'slave')->getRow("SELECT id,real_name,idno,idcardpassed,payment_user_id FROM firstp2p_user WHERE id='{$id}'");
    if (empty($user)) {
        __log__("UserIsNotExists. userId:{$id}");
        continue;
    }

    $deal = Db::getInstance('firstp2p', 'slave')->getRow("SELECT id FROM firstp2p_deal_load WHERE user_id='{$id}'");

    $idnoVerify = new \libs\idno\CommonIdnoVerify();
    $result = $idnoVerify->checkIdno($user['real_name'], $user['idno']);
    __log__("userId:{$id}, deal:".count($deal).", user:".json_encode($user, JSON_UNESCAPED_UNICODE).", result:".json_encode($result, JSON_UNESCAPED_UNICODE));
}
