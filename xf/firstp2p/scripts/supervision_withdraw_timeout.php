<?php
/**
 * 存管-提现订单未落单进行补单的脚本
 *
 * 冻结用户余额，并且提现订单落库
 *
 * @package     scripts
 * @author      guofeng3
 ********************************** 80 Columns *********************************
 */
require_once(dirname(__FILE__) . '/../app/init.php');

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);

use libs\db\Db;
use core\dao\SupervisionWithdrawModel;
use core\dao\UserModel;
use core\dao\DealModel;
use core\service\SupervisionFinanceService;

$db = Db::getInstance('firstp2p', 'master');
$maxId = $db->getOne("SELECT max(id) FROM firstp2p_supervision_withdraw");
$minId = $maxId - 100000;
$createTimeBefore = time()-360;
$sql = "SELECT * FROM firstp2p_supervision_withdraw WHERE create_time <= {$createTimeBefore} AND type IN (".SupervisionWithdrawModel::TYPE_LIMIT_WITHDRAW.' ,'.SupervisionWithdrawModel::TYPE_LIMIT_WITHDRAW_BLACKLIST.') AND withdraw_status  = '.SupervisionWithdrawModel::WITHDRAW_STATUS_PROCESS." AND id >= {$minId} ";
$datas = $db->getAll($sql);
$service = new SupervisionFinanceService();
foreach ($datas as $withdraw)
{
    // 检查订单是否已经落单，如果已落单，则等待支付通知(订单查询失败或者订单存在，则不处理,200005:订单不存在)
    $order = $service->orderSearch($withdraw['out_order_id']);
    if (empty($order) || $order['status'] != SupervisionEnum::RESPONSE_FAILURE || $order['respSubCode'] != '200005') {
        continue;
    }

    try {
        $db->startTrans();
        $userDao = UserModel::instance()->find($withdraw['user_id']);
        $userDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION; //不修改账户余额
        $orderId = $withdraw['out_order_id'];
        $status =  SupervisionWithdrawModel::WITHDRAW_STATUS_FAILED;
        $amount = $withdraw['amount'];
        $withdrawModel = SupervisionWithdrawModel::instance()->find($withdraw['id']);
        $result = $withdrawModel->orderProcess($orderId, $status, $amount);
        $db->commit();
    } catch (\Exception $e)
    {
        echo $e->getMessage();
        $db->rollback();
    }
}
