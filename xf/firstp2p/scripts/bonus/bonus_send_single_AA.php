<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：给AA租车的用户发送红包，共69138个手机号
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';

$money = 10;
$created_at = time();
$updated_at = $created_at + 24 * 60 * 60 * 7;
$pages = 100;
$num_success = 0;
$count = 1000;

//for($i = 0; $i < $pages; $i++) {
$file_name = trim($argv[1]);
$file = __DIR__ . '/users/' . $file_name . '.txt';

if (!is_file($file)) {
    echo "文件不存在$file\n";
    break;
}

$handle = fopen($file, 'r');
while (!feof($handle)) {
    $mobile = trim(fgets($handle, 1024));
    if (empty($mobile)) {
        continue;
    }
    if (!preg_match('/^[0-9]{11}$/', $mobile)) {
        echo "mobile=$mobile\terror=手机号码格式错误\n";
        continue;
    }

    $sql = "SELECT id FROM `%s` where mobile='%s' AND `is_effect` =1 AND `is_delete` =0";
    $sql = sprintf($sql, 'firstp2p_user', $mobile);
    $user = \core\dao\UserModel::instance()->findBySql($sql, array(), true);
    $owner_uid = 0;
    if (isset($user['id'])) {
        $owner_uid = intval($user['id']);
    }

    $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `mobile`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, "%s", %s, %s, %s, %s, %s)';
    $result = $GLOBALS['db']->query(sprintf($insert_sql, $owner_uid, $mobile, $money, 1, 10, $created_at, $updated_at));
    echo "mobile=$mobile\tuid=$owner_uid\tresult=$result\n";
    if ($result) {
        $num_success++;
    }
    if ($num_success % 5000 == 0) {
        sleep(1);
    }
}

echo '红包发送成功',$num_success,"个\n";
