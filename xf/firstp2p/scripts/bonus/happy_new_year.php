<?php
/**
 *-----------------------------------------------------------------------
 * 拜年红包邀请返红包
 * 1、使用rebate_status标记是否返利
 * 2、根据手机好进行返利，不管该手机号是否已经注册
 * 3、脚本每天执行一次
 * 4、按照活动的组ID进行扫描符合条件的手机号进行返红包
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//error_reporting(E_ERROR);
//set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';
use libs\utils\Logger;

ini_set('display_errors', 1);

$bonus_service = new \core\service\BonusService();
$group_id = $bonus_service->encrypt(app_conf('BONUS_HAPPY_NEW_YEAR'));

if ($group_id <= 0) {
    exit("活动无效!\n");
}

$pages = 100;
$count = 10000;
$number_success = 0;

$money = 10;
$created_at = time();
$expired_at = $created_at + 86400 * 3;//返利红包有效期为72小时

$msgcenter = new Msgcenter();
$num_success = 0;

$insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `mobile`, `status`, `money`, `created_at`, `expired_at`) VALUES(%s, "%s", %s, %s, %s, %s)';
$update_sql = 'UPDATE `firstp2p_bonus` SET `rebate_status` = 1 WHERE `id`=%s LIMIT 1';

for($i = 0; $i < $pages; $i++) {

    $sql = "SELECT A.id, A.mobile, A.refer_mobile, B.id as uid FROM %s A LEFT JOIN %s B ON A.refer_mobile=B.mobile ";
    $sql .= " WHERE A.`group_id`=%s AND A.`status`=2 AND A.`rebate_status`=0 ORDER BY B.`id` ASC LIMIT %s, %s";
    $sql = sprintf($sql, 'firstp2p_bonus', 'firstp2p_user', $group_id, $i * $count, $count);
    $list = $GLOBALS['db']->get_slave()->getAll($sql);
    if (count($list) <= 0) {
        break;
    }

    foreach ($list as $user) {
        $result = rebate($user['mobile'], strval($user['refer_mobile']), intval($user['uid']), $money, $user['id'], $created_at, $expired_at, $update_sql, $insert_sql, $msgcenter);
        if ($result) {
            $num_success++;
        }
    }
    $msgcenter->save();
}

echo '红包发送成功',$num_success,"个\n";

function rebate($mobile, $refer_mobile, $uid, $money, $bonus_id, $created_at, $expired_at, $update_sql, $insert_sql, $msgcenter) {

    try {
        $GLOBALS['db']->startTrans();
        $insert_sql = sprintf($insert_sql, $uid, $refer_mobile, 1, $money, $created_at, $expired_at);
        $update_sql = sprintf($update_sql, $bonus_id);
        $result = $GLOBALS['db']->query($update_sql);
        if ($result) {
            $result = $GLOBALS['db']->query($insert_sql);
            if ($result) {
                $result = $GLOBALS['db']->insert_id();
            }
        }
        $GLOBALS['db']->commit();
        $msg = $msgcenter->setMsg($refer_mobile, $uid, array('mobile'=> $mobile, 'money' => format_price($money)), 'TPL_SMS_BONUS_HAPPY_NEW_YEAR', '邀好友返现金红包');
    } catch(Exception $e) {
        $result = 0;
        $GLOBALS['db']->rollBack();
    }
    $log = array(
        'type' => 'bonus_happy_new_year',
        'bonus_id' => $bonus_id,
        'user' => 'uid:' . $uid . 'refer_mobile:' . $refer_mobile . 'mobile:' . $mobile,
        'money' => $money,
        'result' => $result,
        'path' => __FILE__,
        'msg' => "拜年红包返利",
        'time' => time()
    );
    Logger::wlog($log);
    return $result;

}
