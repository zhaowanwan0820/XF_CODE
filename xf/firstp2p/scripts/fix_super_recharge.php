<?php
/**
 * 解冻划转失败记录
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

require_once(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('__DEBUG', false);

use core\dao\UserModel;
use core\dao\UserBankcardModel;
use libs\utils\PaymentApi;

$db = \libs\db\Db::getInstance('firstp2p', 'master');
$data =
[
'894104' => '300000',
'4615615' => '9996',
'6499552' => '546226',
'7852464' => '9998000',
'9846980' => '7000',
];

foreach ($data as $userId => $amountCent)
{
    $userDao = UserModel::instance()->find($userId);
    $userDao->changeMoneyAsyn = true;
    $userDao->changeMoney(- bcdiv($amountCent, 100, 2), '余额划转失败', '网信理财账户余额划转到网贷P2P账户余额', 0, 0, UserModel::TYPE_LOCK_MONEY);
}

echo "修复完成";
