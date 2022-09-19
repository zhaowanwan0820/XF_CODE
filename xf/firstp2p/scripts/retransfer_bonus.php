<?php
require(dirname(__FILE__) . '/../app/init.php');
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\PtpTaskClient AS PtpTaskClient;
use libs\utils\PaymentApi;
use libs\utils\Alarm;

\libs\utils\Script::start();

ini_set('memory_limit', '2048M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$i = 0;
$fileData = '';
do {
$fileData = file_get_contents('http://static.firstp2p.com/attachment/201609/19/11/447dd184133c7817c692bb39a2914f30/b7fe864ada34d1880fec753246d7e866.csv');
}
while(empty($fileData) && $i  ++ < 5);

if (empty($fileData))
{
    echo 'cannot read file.'.PHP_EOL;
    exit;
}
$userIds = explode("\n", $fileData);
// 转入用户id
$bonusUserId = 324579;

if (!empty($userIds))
{
    // 红包用户加钱
    $bonusUser = \core\dao\UserModel::instance()->find($bonusUserId);
    $db = \libs\db\Db::getInstance('firstp2p');
    foreach ($userIds as $userId)
    {

        $db->startTrans();
        try {
            // 转出用户
            $user = \core\dao\UserModel::instance()->find($userId);
            if (empty($user))
            {
                throw new \Exception('用户不存在');
            }
            $user->changeMoneyAsyn = true;
            // 检查用户余额是否足够
            if (bccomp($user['money'], '5.00', 2) >= 0)
            {
                // 红包用户扣减
                $user->changeMoney(-5.00, '系统余额修复', '已锁定的新手红包1.0、2.0套利用户，追回5元费用');
            }
            else
            {
                throw new \Exception("余额不足");
            }
            // 红包账户加钱
            $bonusUser->changeMoney(5.00, '系统余额修复', '已锁定的新手红包1.0、2.0套利用户，追回5元费用');
            // 财务复核记录
            $financeAuditData = [
                'into_name' => $bonusUser['user_name'],
                'out_name' => $user['user_name'],
                'money' => '5.00',
                'status' => 3, //审核通过
                'log' => '系统余额修复，自动审批通过',
                'create_time' => get_gmtime(),
                'update_time' => get_gmtime(),
                'admin' => 'system',
                'info' => '已锁定的新手红包套利用户处理脚本',
            ];
            $db->autoExecute('firstp2p_finance_audit', $financeAuditData, 'INSERT');
            $affected_rows = $db->affected_rows();
            if ($affected_rows <= 0)
            {
                throw new \Exception('财务复核记录插入失败');
            }

            // 支付转账同步
            $transferData = [[
                'payerId' => $userId,
                'receiverId' => $bonusUserId,
                'repaymentAmount' => '500',
                'outOrderId' => 'retransfer',
            ]];
            $pushResult = \core\dao\FinanceQueueModel::instance()->push(['orders' => $transferData], 'transfer');
            if(!$pushResult)
            {
                throw new \Exception('转账记录插入失败');
            }

            $db->commit();
            echo 'user '.$userId.' bonus transfer successfully'.PHP_EOL;
        }
        catch(\Exception $e)
        {
            $db->rollback();
            echo 'user '.$userId.' bonus transfer failed, reason:'.$e->getMessage().PHP_EOL;
        }

    }
}
