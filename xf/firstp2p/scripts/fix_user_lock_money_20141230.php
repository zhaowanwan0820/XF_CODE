<?php
/**
 * 自动生成对账单程序
 * 每天晚上0点开始执行，生成前一天的对账数据
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

require_once(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('__DEBUG', false);

use core\dao\UserModel;
use core\dao\UserCarryModel;

$tofix = array(
    '2515' => array(
        '20640',
    ),
);


foreach ($tofix as $userId => $withdrawArray) {
    $user = UserModel::instance()->find($userId);
    try {
        foreach ($withdrawArray as $withdrawId) {
            $userCarry = UserCarryModel::instance()->find($withdrawId);
            $user->changeMoney($userCarry->money, '系统余额修正', "用户提现:{$withdrawId} 扣减冻结余额失败修正" , '0', 0, \core\dao\UserModel::TYPE_DEDUCT_LOCK_MONEY, true);
        }
    }
    catch (\Exception $e) {
        echo "\n修复{$userId}的{$withdrawId}失败:".$e->getMessage() . "\n";
    }
}
echo "修复完成";
