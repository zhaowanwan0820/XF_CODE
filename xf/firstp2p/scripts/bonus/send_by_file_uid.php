<?php
/**
 *-----------------------------------------------------------------------
 * 1、按照文件中的uid发送红包，默认5元有效期24小时
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';

$money = 5;
$created_at = time();
$updated_at = $created_at + 24 * 60 * 60 * 1;
$num_success = 0;

$file_name = trim($argv[1]);
$file = __DIR__ . '/users/' . $file_name . '.txt';

if (!is_file($file)) {
    echo "文件不存在$file\n";
    break;
}

$handle = fopen($file, 'r');
while (!feof($handle)) {
    $uid = trim(fgets($handle, 1024));
    if (empty($uid)) {
        continue;
    }
    if (!preg_match('/^[0-9]+$/', $uid)) {
        echo "id=$uid\terror=uid格式错误\n";
        continue;
    }

    $sql = "SELECT mobile FROM `%s` where id='%s' AND `is_effect` =1 AND `is_delete` =0";
    $sql = sprintf($sql, 'firstp2p_user', $uid);
    $user = \core\dao\UserModel::instance()->findBySql($sql, array(), true);
    $mobile = '';
    if (isset($user['mobile'])) {
        $mobile = intval($user['mobile']);
    }

    $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `mobile`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, "%s", %s, %s, %s, %s, %s)';
    $result = $GLOBALS['db']->query(sprintf($insert_sql, $uid, $mobile, $money, 1, 9, $created_at, $updated_at));
    echo "mobile=$mobile\tuid=$uid\tresult=$result\n";
    if ($result) {
        $num_success++;
    }
    if ($num_success % 5000 == 0) {
        sleep(1);
    }
}

echo '红包发送成功',$num_success,"个\n";
