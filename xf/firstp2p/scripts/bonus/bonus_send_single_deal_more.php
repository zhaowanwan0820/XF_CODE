<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：自平台成立以来至4月10日12点，为投资满两次的用户发送5
 * 元投资红包，红包有效期24小时
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

$money = 5;
$created_at = time();
$updated_at = $created_at + 86400;
$pages = 500;
$num_success = 0;
$count = 1000;

$user_tag_service = new \core\service\UserTagService;
$tag_id = array_pop($user_tag_service->getTagIdsByConstName('BID_MORE'));

$today = date('Ymd');

if ($tag_id <= 0) {
    exit("标签错误。\n");
}

for($i = 0; $i < $pages; $i++) {

    $sql = "SELECT B.id, B.mobile FROM `%s` A INNER JOIN `%s` B ON A.uid = B.id where A.tag_id = %s && A.created_at <= '2015-04-10 12:00:00' ORDER BY B.id ASC LIMIT %s, %s";
    $sql = sprintf($sql, 'firstp2p_user_tag_relation',  'firstp2p_user', intval($tag_id), $i * $count, $count);
    $list = $GLOBALS['db']->get_slave()->getAll($sql);
    if (count($list) <= 0) {
        break;
    }
    //$msgcenter = new Msgcenter();
    foreach ($list as $user) {
        $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, %s, %s, %s, %s, %s)';
        $result = $GLOBALS['db']->query(sprintf($insert_sql, $user['id'], $money, 1, 9, $created_at, $updated_at));
        if ($result) {
            //if ( '20150411' == $today) {
            //    $msg = $msgcenter->setMsg($user['mobile'], $user['id'], array('money' => format_price($money)), 'TPL_SMS_BONUS_EVENT', '老用户回馈红包');
            //}
            $num_success++;
        }
    }
    //$msgcenter->save();
    if ($num_success % 10000 == 0) {
        sleep(1);
    }
}

echo '红包发送成功',$num_success,"个\n";
