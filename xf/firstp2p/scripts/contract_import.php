<?php
require_once(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealExtModel;
use core\dao\ContractModel;

$deal_id = $argv[1];
$type = $argv[2] ? $argv[2] : 1;

$csv_name = "/tmp/contract_" . $deal_id . ".csv";
$handle = fopen($csv_name, "w+");

$deal_dao = new DealModel();
$deal = $deal_dao->find($deal_id);

$deal_name = $deal->name;

$borrow_user = UserModel::instance()->find($deal->user_id);

$borrow_user_name = $borrow_user->real_name;

$con_list = ContractModel::instance()->findAll("`deal_id`='{$deal_id}' AND `type`='{$type}' AND `user_id`!='{$borrow_user->id}'");

$title = array("借款标题","合同编号","出借人姓名","出借人身份证号","出借金额","借款人");
$title = iconv("utf-8", "gbk", implode(',', $title));
fputcsv($handle, explode(',', $title));

foreach ($con_list as $k => $v) {
    $load = getLoadByConid($v['id']);
    $money = $load['money'];
    $user_id = $v['user_id'];
    $user = UserModel::instance()->find($user_id);
    $arr = array($deal_name, $v['number'], $user['real_name'], $user['idno'], $money, $borrow_user_name);
    $row = $deal_name . "||\t" . $v['number'] . "||" . $user['real_name'] . "||\t" . $user['idno'] . "||" . $money . "||" . $borrow_user_name;

    $arr = explode("||", $row);
    $arr = iconv("utf-8", "gbk", implode(',', $arr));
    fputcsv($handle, explode(',', $arr));
}

fclose($handle);
