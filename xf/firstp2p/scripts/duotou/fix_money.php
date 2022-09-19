<?php

require_once dirname(__FILE__).'/../../app/init.php';

error_reporting(0);
set_time_limit(0);

use core\dao\UserModel;
use core\dao\DealModel;

$user_model = new UserModel();
$user = $user_model->find(5830491);
$user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
$result = $user->changeMoney(550, '系统修正冻结余额', '系统修正冻结金额-智多鑫', 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);
unset($user);

//红包好增加余额
$user = $user_model->find(7091228);
$user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
$result = $user->changeMoney(550, '系统修正余额', '系统修正金额-智多鑫', 0, 0, UserModel::TYPE_MONEY);
unset($user);


/**
$user = $user_model->find(11192765);
$user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
$result = $user->changeMoney(2, '系统修正余额', '系统修正金额-智多鑫', 0, 0, UserModel::TYPE_MONEY);
unset($user);
$user = $user_model->find(8229699);
$user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
$result = $user->changeMoney(281.40, '系统修正余额', '系统修正金额-智多鑫', 0, 0, UserModel::TYPE_MONEY);
**/
