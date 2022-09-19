<?php
/**
 * 快照脚本-用户余额
 *
 * 示例：0 23 * * * /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/snapshot_user_money.php 20170907 1 500000
 *
 * 每天定时把用户的网信余额、网贷余额进行快照
 *
 * @package     scripts
 * @author      guofeng3
 ********************************** 80 Columns *********************************
 */
require_once(dirname(__FILE__) . '/../app/init.php');

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('memory_limit', '2048M');
ini_set('display_errors' , 1);
set_time_limit(0);

use libs\utils\Script;
use core\service\PaymentCheckService;
use core\service\ncfph\AccountService as PhAccountService;
use core\dao\UserThirdBalanceModel;
use core\dao\UserMoneySnapshotModel;

class SnapshotUserMoney {
    /*
     * 每次遍历用户数量
     * int
     */
    private $count = 3000;

    /**
     * 用户起始ID
     * @var int
     */
    private $startId;

    /**
     * 用户截止ID
     * @var int
     */
    private $endId;

    public function __construct($startId = 0, $endId = 0) {
        $this->paymentCheckService = new PaymentCheckService();
        $this->phAccountService = new PhAccountService();
        $this->snapshotDate = date('Ymd');
        $this->startId = max(1, (int)$startId);
        $this->endId = (int)$endId;
    }

    public function run() {
        // 用户总数
        $totalCount = $snapshotCount = 0;
        // 获取用户的最大ID
        if ($this->endId > 0 && $this->endId > $this->startId) {
            $maxUserId = $this->endId;
        }else{
            $maxUserId = $this->paymentCheckService->getMaxUserId();
        }

        for ($i = $this->startId; $i <= $maxUserId; $i += $this->count) {
            $step = min($i+$this->count, $maxUserId);
            // 批量获取用户的网信余额
            $wxMoney = $this->paymentCheckService->getUserMoney(sprintf('id BETWEEN %d AND %d', $i, $step));
            if (empty($wxMoney)) {
                usleep(10);
                continue;
            }
            $userIds = array_keys($wxMoney);
            if (empty($userIds)) {
                continue;
            }

            $totalCount += count($userIds);

            //暂时把userId当作accountId使用，多账户之后再修改@todo
            $p2pSupervisionMoney = $this->phAccountService->getInfoByIds($userIds, false);
            unset($userIds);

            foreach ($wxMoney as $userId => $moneyData) {
                $p2pMoney = !empty($p2pSupervisionMoney[$userId]['money']) ? $p2pSupervisionMoney[$userId]['money'] : '0.00';
                // 网信余额、网贷余额都小于0
                if (bccomp($moneyData['money'], '0.00', 2) <= 0 && bccomp($p2pMoney, '0.00', 2) <= 0) {
                    continue;
                }

                ++$snapshotCount;
                // 记录用户余额快照表
                $snapshotMoney = bcmul($moneyData['money'], 100, 0);
                $snapshotP2pMoney = bcmul($p2pMoney, 100, 0);
                $ret = UserMoneySnapshotModel::instance()->addSnapshot($userId, $snapshotMoney, $snapshotP2pMoney, 0, $this->snapshotDate);
                Script::log(sprintf('%s::%s|记录用户的网信余额、网贷余额，userId:%d, snapshotMoney:%d, snapshotP2pMoney:%d, 快照记录ID:%d', __CLASS__, __FUNCTION__, $userId, $snapshotMoney, $snapshotP2pMoney, (int)$ret));
            }
        }
        Script::log(sprintf('%s::%s|用户余额快照执行完毕, 执行日期:%d, 用户总数:%d, 记录快照的用户数:%d', __CLASS__, __FUNCTION__, $this->snapshotDate, $totalCount, $snapshotCount));
        return true;
    }
}

Script::start();
// 同时仅允许一个脚本运行
$cmd = sprintf('ps aux | grep \'%s\' | grep -v grep | grep -v vim | grep -v %d', basename(__FILE__), posix_getpid());
$handle = popen($cmd, 'r');
$scriptCmd = fread($handle, 1024);
if ($scriptCmd) {
    exit("snapshot_user_money.php is running!\n");
}

// 起始用户ID
$startId = isset($argv[1]) ? (int)$argv[1] : 0;
// 截止用户ID
$endId = isset($argv[2]) ? (int)$argv[2] : 0;
$obj = new SnapshotUserMoney($startId, $endId);
$obj->run();
Script::end();
