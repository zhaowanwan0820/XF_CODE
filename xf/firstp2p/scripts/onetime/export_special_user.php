<?php
/**
 * 导出特殊用户
 * 机构列表项下的咨询及担保户关联ID为个人会员列表的企业
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;

$db = Db::getInstance('firstp2p');

Logger::info('export_special_user start');

$sql = sprintf("SELECT `id`, `type`, `name`, `user_id` FROM firstp2p_deal_agency");
$userList = $db->getAll($sql);

foreach ($userList as $val) {
    $userId = $val['user_id'];
    $sql = sprintf("SELECT `id`, `user_name`, `mobile`, `mobile_code`, `mobile` FROM firstp2p_user WHERE id = %d", $userId);
    $userInfo = $db->getRow($sql);
    $row = [
        'agency_id' => $val['id'],
        'agency_type' => $val['type'],
        'agency_name' => $val['name'],
        'user_id' => $userId,
        'user_name' => $userInfo['user_name'],
        'is_enterprise_user' => 0,
    ];
    if( !empty($userInfo['mobile']) && substr($userInfo['mobile'], 0, 1) == '6'
        && (empty($userInfo['mobile_code']) || $userInfo['mobile_code'] == '86') ) {
            $row['is_enterprise_user'] = 1;
    }
    Logger::info('export_special_user,csv:' . implode(',', $row));
}

Logger::info('export_special_user end');
