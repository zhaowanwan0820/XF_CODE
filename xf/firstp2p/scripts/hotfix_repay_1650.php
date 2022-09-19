<?php
require(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

error_reporting(0);

define("SQL_DEBUG", true);

use core\dao\DealLoadModel;
use core\dao\DealRepayModel;
use core\dao\UserCarryModel;
use core\dao\UserModel;

$deal_id = 1650;

$deal_repay_model = new DealRepayModel();
$deal_repay = $deal_repay_model->findBy("`deal_id`='{$deal_id}'");
$repay_time = $deal_repay->true_repay_time; //1406679100

file_put_contents("repay_time.log", $repay_time."\n", FILE_APPEND);

$deal_load_model = new DealLoadModel();
$loan_user_list = $deal_load_model->findAll("`deal_id`='{$deal_id}'");

$user_arr = array();
$user_model = new UserModel();
foreach ($loan_user_list as $load) {
    file_put_contents("loan_user.log", $load['user_id']."\n", FILE_APPEND);
    $user_arr[] = $load['user_id'];
}

$loan_user_str = implode(",", $user_arr);
$user_carry_model = new UserCarryModel();
$carry_list = $user_carry_model->findAll("`create_time` >= '{$repay_time}' AND `user_id` IN ({$loan_user_str})");

$carry_user_arr = array();

foreach ($carry_list as $carry) {
    $carry_user_arr[] = $carry->user_id;
    file_put_contents("carry.log", $carry->id."\t".$carry->user_id."\n", FILE_APPEND);
    //$user_carry_model::$db->query("DELETE FROM `".$user_carry_model->tableName()."` WHERE `id`='{$carry['id']}'");
}

foreach ($user_arr as $user_id) {
    if (in_array($user_id, $carry_user_arr)) {
        continue;
    }
    file_put_contents("user.log", $user_id."\n", FILE_APPEND);
    $user = $user_model->find($user_id);
    $user->changeMoney(-10.02, "系统修正", "系统修正", 0, 0, 0);
}
