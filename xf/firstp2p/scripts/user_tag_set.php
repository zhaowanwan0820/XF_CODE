<?php
/**
 *----------------------------------------------------------
 * (给散播成功的用户以及使用红包投资的用户打标记）（已完成）
 * 给全部用户标记性别信息（已完成）
 * 给女性投资用户添加标记
 *----------------------------------------------------------
 * @version V1.0
 */
require_once dirname(__FILE__).'/../app/init.php';

ini_set('memory_limit', '512M');
set_time_limit(0);

$count = intval($argv[1]) > 0 ? intval($argv[1]) : 10000;

$user_tag_service = new \core\service\UserTagService();

$num_success = 0;
//$tag_ids_male = $user_tag_service->getTagIdsByConstName('USER_MALE');
//$tag_ids_female = $user_tag_service->getTagIdsByConstName('USER_FEMALE');
$tag_ids_female = $user_tag_service->getTagIdsByConstName('INVITE_USER_HALF');

$sql = "select refer_user_id, count(refer_user_id) as c from firstp2p_user where refer_user_id >0 group by refer_user_id order by c desc limit 18990";

if (!empty($tag_ids_female) && is_array($tag_ids_female)) {
    $list = $GLOBALS['db']->getAll(sprintf($sql));

    if (count($list) <= 0) {
        break;
    }
    foreach ($list as $row) {
        $result = $user_tag_service->addUserTags($row['refer_user_id'], $tag_ids_female);
        $num_success++;
    }
    echo "共{$num_success}个用户。\n";
} else {
    //echo "标签不存在 “USER_MALE|USER_FEMALE” \n";
    echo "标签不存在 “BID_MORE_FEMALE” \n";
}

/*
if (!empty($tag_ids_female) && is_array($tag_ids_female)) {
    for($i = 0; $i < 100; $i++) {

           $sql=" SELECT distinct(A.user_id) FROM firstp2p_deal_load A INNER JOIN firstp2p_user B ON A.user_id = B.id WHERE A.`money` > 0 AND B.sex = 1 ORDER BY A.user_id ASC LIMIT %s,%s";
        $list = $GLOBALS['db']->getAll(sprintf($sql, ($i * $count), $count));
        if (count($list) <= 0) {
            break;
        }
        foreach ($list as $row) {
            $result = $user_tag_service->addUserTags($row['user_id'], $tag_ids_female);
            $num_success++;
        }
        usleep(10000);
    }
    echo "共{$num_success}个用户。\n";
} else {
    //echo "标签不存在 “USER_MALE|USER_FEMALE” \n";
    echo "标签不存在 “BID_MORE_FEMALE” \n";
}
 */
