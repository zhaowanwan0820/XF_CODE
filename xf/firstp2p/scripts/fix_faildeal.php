<?php

require_once dirname(__FILE__) . '/../app/init.php';

use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\UserLogModel;
use core\dao\DealLoadModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$deal_id = isset($argv[1]) ? intval($argv[1]) : 0;
if ($deal_id <= 0) {
    exit('id错误');
}

$deal = DealModel::instance()->find($deal_id);

$load_list = DealLoadModel::instance()->findAll("`deal_id`='{$deal_id}'");

foreach ($load_list as $v) {
    $user_id = $v['user_id'];
    $load_id = $v['id'];
    $money = $v['money'];

    try{
        $params = array(
            ':user_id' => $user_id,
            ':deal_load_id' => $load_id,
        );
        $log = UserLogModel::instance()->findBy("`user_id`=':user_id' AND `log_info`='取消投标' AND `note` LIKE '%单号:deal_load_id'", "*", $params);
        if ($log) {
            continue;
        }

        $GLOBALS['db']->startTrans();
 
        $note = '编号' . $deal_id .' ' . $deal['name'] . '，单号' . $v['id'];
        $user = UserModel::instance()->find($user_id);
        $user->changeMoney(-$v['money'], "取消投标", $note, 0, 0, 1);

        echo "succ | {$user_id} | {$load_id} | {$money}\n";

        $GLOBALS['db']->commit();
    } catch (\Exception $e) {
        echo "fail | {$user_id} | {$load_id} | {$money}\n";

        $GLOBALS['db']->rollback();
    }
}

echo "修复完成\n";
