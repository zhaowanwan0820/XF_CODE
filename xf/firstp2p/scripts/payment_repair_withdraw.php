<?php
/**
 * 提现-冷数据处理脚本
 *
 * 修复提现冷数据遇到的后台无法批准/拒绝的问题
 * 提现-业务状态(0:失败1:成功2:未处理3:处理中4:已撤销)
 * 提现-支付状态
 *
 * @example /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/payment_repair_withdraw.php 用户ID 提现ID
 *
 * @package     scripts
 * @author      guofeng3
 * @copyright   (c) 2016, Wxlc Corporation. All rights reserved.
 * @History:
 *     1.0.0 | guofeng3 | 2016-08-04 15:00:00 | initialization
 ********************************** 80 Columns *********************************
*/

ini_set('memory_limit', '512M');
set_time_limit(0);

require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Script;
use libs\db\Db;
use core\dao\UserModel;
use core\dao\UserCarryModel;

// 脚本开始
Script::start();

class PaymentRepairWithdraw
{
    public function __construct($userId, $carryId)
    {
        // 用户ID
        $this->userId = intval($userId);
        // 冷数据中的提现ID
        $this->carryId = intval($carryId);
    }

    /**
     * 更新提现冷数据的状态、给用户解冻
     */
    public function run()
    {
        try {
            if (empty($this->userId) || empty($this->carryId))
            {
                throw new \Exception('userId or carryId Is Illegal');
            }
            // 检查用户是否存在
            $userDao = new UserModel();
            $userInfo = $userDao->find($this->userId);
            if (empty($userInfo))
            {
                throw new \Exception('userInfo Is Not Exist');
            }

            // 检查提现记录是否存在
            $dbMoved = Db::getInstance('firstp2p_moved');
            // 开启事务
            $dbMoved->startTrans();
            $sql = sprintf('SELECT `id`,`money`,`fee`,`status`,`withdraw_status` FROM `firstp2p_user_carry` WHERE `id` = %d AND `user_id` = %d AND status != %d AND withdraw_status != %d', $this->carryId, $this->userId, 4, UserCarryModel::WITHDRAW_STATUS_FAILED);
            $userCarryInfo = $dbMoved->getRow($sql);
            if (empty($userCarryInfo) || empty($userCarryInfo['money']))
            {
                throw new \Exception("carryInfo Is Not Exist");
            }

            // 更新提现冷数据的状态(status改成财务拒绝，withdraw_status改成提现失败)
            $carryData = array('status'=>4, 'withdraw_status'=>UserCarryModel::WITHDRAW_STATUS_FAILED, 'update_time'=>get_gmtime());
            $dbMoved->autoExecute('firstp2p_user_carry', $carryData, 'UPDATE', sprintf('id=%d AND user_id=%d', $this->carryId, $this->userId));
            $affectedRows = $dbMoved->affected_rows();
            // 记录日志
            Script::log(sprintf('Method:%s, userId:%d, carryId:%d, oldStatus:%d, oldWithdrawStatus:%d, newStatus:%d, newWithdrawStatus:%d, 更新提现冷数据', __METHOD__, $this->userId, $this->carryId, $userCarryInfo['status'], $userCarryInfo['withdraw_status'], 4, UserCarryModel::WITHDRAW_STATUS_FAILED), Logger::INFO);
            if ($affectedRows <= 0)
            {
                throw new \Exception('更新提现冷数据失败');
            }

            // 给用户解冻
            $unLockMoney = bcadd($userCarryInfo['money'], $userCarryInfo['fee'], 2);
            $changeRet = $userInfo->changeMoney(-$unLockMoney, '提现失败', '', 0, 0, UserModel::TYPE_LOCK_MONEY);
            if (!$changeRet)
            {
                throw new \Exception('changeMoney失败');
            }
            // 提交事务
            $dbMoved->commit();
            // 记录日志
            Script::log(sprintf('Method:%s, userId:%d, carryId:%d, unLockMoney:%s, 更新提现冷数据成功|changeMoney成功', __METHOD__, $this->userId, $this->carryId, $unLockMoney), Logger::ERR);
        } catch (\Exception $e) {
            // 回滚事务
            isset($dbMoved) && $dbMoved->rollback();
            // 记录日志
            Script::log(sprintf('Method:%s, userId:%d, carryId:%d, ExceptionMsg:%s', __METHOD__, $this->userId, $this->carryId, $e->getMessage()), Logger::ERR);
        }
    }
}

$userId = isset($argv[1]) ? $argv[1] : 0;
$carryId = isset($argv[2]) ? $argv[2] : 0;
$handle = new PaymentRepairWithdraw($userId, $carryId);
$handle->run();
// 脚本结束
Script::end();