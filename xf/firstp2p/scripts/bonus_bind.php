<?php
/**
 * 用户账号与红包绑定
 * 针对获取红包后注册的用户，没有成功绑定账号的用户，进行重新绑定
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\Logger;

set_time_limit(0);
$num_success = 0;

$sql = "SELECT distinct(`mobile`) FROM ".DB_PREFIX."bonus WHERE owner_uid=0 AND mobile!=''";
$list = $GLOBALS['db']->get_slave()->getAll($sql);

$user_model = new \core\dao\UserModel();
$bonus_service = new \core\service\BonusService();

foreach ($list as $row) {
    $user = $user_model->findBy(("mobile='{$row['mobile']}'"), 'id');
    if (intval($user['id']) == 0) {
        continue;
    }
    $res = $bonus_service->bind(intval($user['id']), $row['mobile']);
    $num_success += $res;
    Logger::wLog(array('data' => "result:{$res}|uid:".$user['id'], 'script/bonus_bind'));
    usleep(1000);
}

echo '共',count($list),'个手机号，成功绑定',$num_success,"个\n";

