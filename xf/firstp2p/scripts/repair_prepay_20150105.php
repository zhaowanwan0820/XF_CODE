<?php
require(dirname(__FILE__) . '/../app/init.php');

FP::import("libs.libs.msgcenter");
FP::import("app.deal");
FP::import("libs.libs.user");

SiteApp::init(); //->run();

error_reporting(0);
set_time_limit(0);

use core\dao\DealPrepayModel;
use core\dao\DealModel;

$arr_deal = array(11037, 11038, 11025, 10749, 9887, 11065, 11041, 11040, 9352, 9348, 9347, 9345, 9279, 9269);

$prepay_model = new DealPrepayModel(); 
$total = 0;

foreach ($arr_deal as $deal_id) {
    $deal = DealModel::instance()->find($deal_id);
    $prepay = $prepay_model->findBy("`deal_id`='{$deal_id}' AND `status`='1'");
    $prepay_money = $prepay->prepay_money;
    
    lock_money($prepay_money, $deal['user_id'], $message = "系统余额修正",1,'编号'.$deal['id'].' '.$deal['name']);

    $total += $prepay_money;
}

echo $total."\n";
