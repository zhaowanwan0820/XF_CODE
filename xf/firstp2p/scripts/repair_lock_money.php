<?php
require(dirname(__FILE__) . '/../app/init.php');

FP::import("libs.libs.msgcenter");
FP::import("app.deal");
FP::import("libs.libs.user");

SiteApp::init(); //->run();

error_reporting(0);
set_time_limit(0);

use core\dao\DealPrepayModel;
use app\models\dao\DealLoanRepay;
use core\dao\FinanceQueueModel;
use core\dao\DealModel;
use core\dao\UserModel;

$file = $argv[1];
if (!$file) {
    echo "file empty";
    exit;
}

$handle = fopen($file, "r");
if (!$handle) {
    echo "file does not exist";
    exit;
}

$user_model = new UserModel();

while ( ($line = fgets($handle, 1024)) !== false) {
    $str = trim($line);
    $arr = explode(",", $str);
    if (count($arr) != 2) {
        echo $str."\n";
        continue;
    }

    $user_name = $arr[0];
    $money = $arr[1];

    $user = $user_model->findBy("`user_name`='{$user_name}'");
    if (!$user) {
        echo $str."\n";
        continue;
    }

    try {
        $result = $user->changeMoney($money, "系统修正冻结余额", "系统修正冻结金额", 0, 0, 1);
        if (!$result) {
            echo $str."\tchange money 失败\n";
            continue;
        }
    } catch(\Exception $e) {
        echo $str."\t".$e->getMessage()."\n";
        continue;
    }

    echo $str."\tsuccess\n";
}

echo "done";
