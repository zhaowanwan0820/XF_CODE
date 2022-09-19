<?php
/**
 *----------------------------------------------------------
 * (给使用红包投资过的用户打标记）（已完成）
 *----------------------------------------------------------
 * @version V1.0
 */
require_once dirname(__FILE__).'/../../app/init.php';

ini_set('memory_limit', '512M');
set_time_limit(0);

$count = intval($argv[1]) > 0 ? intval($argv[1]) : 10000;
$tag_name = "BONUS_USED_USER";
$user_tag_service = new \core\service\UserTagService();

$num_success = 0;
$tag_ids_female = $user_tag_service->getTagIdsByConstName($tag_name);

$sql = "SELECT distinct(owner_uid) as `user_id` FROM `firstp2p_bonus` where status=2";

if (!empty($tag_ids_female) && is_array($tag_ids_female)) {
    $list = $GLOBALS['db']->getAll(sprintf($sql));

    if (count($list) <= 0) {
        break;
    }
    foreach ($list as $row) {
        $result = $user_tag_service->addUserTags($row['user_id'], $tag_ids_female);
        $num_success++;
    }
    echo "共{$num_success}个用户。\n";
} else {
    echo "标签不存在 “{$tag_name}” \n";
}

