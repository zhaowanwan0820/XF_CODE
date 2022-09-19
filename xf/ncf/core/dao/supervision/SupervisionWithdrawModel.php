<?php
namespace core\dao\supervision;

use NCFGroup\Common\Library\Zhuge;
use libs\common\WXException;
use libs\utils\PaymentApi;
use libs\utils\Site;
use libs\utils\Alarm;
use core\enum\AccountEnum;
use core\enum\SupervisionEnum;
use core\dao\BaseModel;
use core\dao\deal\DealModel;
use core\service\account\AccountService;
use core\service\account\AccountLimitService;

/**
 * 存管账户提现记录
 * 涉及到的函数
 * SupervisionFinanceService/bidElecWithdraw 提现至银信通电子账户
 * SupervisionFinanceService/bidElecWithdrawNotify 提现至银信通电子账户回调
 *
 **/
class SupervisionWithdrawModel extends BaseModel {

    /**
     * 创建记录单
     * @param integer $accountId 账户ID
     * @param integer $amount 订单金额
     * @param string $outOrderId 外部订单号
     * @return boolean
     */
    public function createOrder($accountId, $amount, $outOrderId, $bidId = 0, $type = SupervisionEnum::TYPE_TO_BANKCARD, $limitId = 0) {
        // 获取表名
        $tableName = $this->tableName();
        $sql = "SELECT COUNT(*) AS orderInfo FROM `{$tableName}` WHERE out_order_id = '{$outOrderId}'";
        $data = $this->db->getRow($sql);
        if (!empty($data['orderInfo'])) {
           return true;
        }

        try {
            $this->db->startTrans();
            if (empty($accountId) || empty($amount) || empty($outOrderId)) {
                throw new WXException('ERR_PARAM');
            }
            $insertData = [];
            $insertData['user_id'] = intval($accountId);
            $insertData['amount'] = intval($amount);
            $insertData['out_order_id'] = intval($outOrderId);
            $insertData['withdraw_status'] = SupervisionEnum::WITHDRAW_STATUS_NORMAL;
            $insertData['type'] = $type;
            $insertData['create_time'] = time();
            $insertData['site_id'] = Site::getId();
            $insertData['limit_id'] = $limitId;
            if (!empty($bidId)) {
                $insertData['bid'] = intval($bidId);
            }
            // 银信通直接做资产中心的余额冻结操作
            if ($type == SupervisionEnum::TYPE_ENTRUSTED || $type == SupervisionEnum::TYPE_LOCKMONEY || $type == SupervisionEnum::TYPE_LIMIT_WITHDRAW || $type == SupervisionEnum::TYPE_LIMIT_WITHDRAW_BLACKLIST) {
                // 受托支付同步更新提现记录状态为处理中
                $insertData['withdraw_status'] = SupervisionEnum::WITHDRAW_STATUS_PROCESS;
                // 唯一订单号
                $bizToken = ['orderId' => $outOrderId];
                $changeMoneyResult = AccountService::changeMoney($accountId, bcdiv($amount, 100, 2), '提现申请', '网贷账户提现申请', AccountEnum::MONEY_TYPE_LOCK, false, true, 0, $bizToken);
                if (!$changeMoneyResult) {
                    throw new WXException('ERR_WITHDRAW_CREATE_ORDER');
                }
            }
            try {
                $this->db->insert($tableName, $insertData);
            } catch(\Exception $e) {
                if ($e->getCode() == '1062') {
                    return true;
                }
            }
            $this->db->commit();
            return true;
        } catch(\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('Supervision Withdraw createOrder FAILED, code:'.$e->getCode().', message:'.$e->getMessage());
            return false;
        }
    }

    public function updateOrderById($id, $updateData) {
        if (empty($id) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }
        if (empty($updateData['update_time'])) {
            $updateData['update_time'] = time();
        }
        $id = addslashes(trim($id));
        $this->db->autoExecute($this->tableName(), $updateData, 'UPDATE', " id = '{$id}'");
        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function updateOrderByOutId($outOrderId, $updateData, $condition = '') {
        if (empty($outOrderId) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }
        if (empty($updateData['update_time'])) {
            $updateData['update_time'] = time();
        }
        if ($condition != '') {
            $condition = ' AND '.$condition;
        }
        $where = sprintf(" out_order_id = '%d'%s", $outOrderId, $condition);
        $this->db->autoExecute($this->tableName(), $updateData, 'UPDATE', $where);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function getWithdrawRecordByOutId($outOrderId) {
        if (empty($outOrderId)) {
            return false;
        }
        // 获取表名
        $tableName = $this->tableName();
        $outOrderId = intval(trim($outOrderId));
        return $this->db->getRow("SELECT * FROM `{$tableName}` WHERE out_order_id = '{$outOrderId}'");
    }

    /**
     * 获取用户最后一次提现成功记录
     */
    public function getUserLastWithdraw($userId) {
        if (empty($userId)) {
            return false;
        }
        // 获取表名
        $tableName = $this->tableName();
        $userId= intval(trim($userId));
        return $this->db->getRow("SELECT * FROM firstp2p_supervision_withdraw WHERE user_id = '{$userId}' AND withdraw_status= ".SupervisionEnum::WITHDRAW_STATUS_SUCCESS." ORDER BY id DESC limit 1");
    }


    /**
     * 根据账户ID、标的ID查询提现成功的记录
     * @param int $accountId 账户ID
     * @param int $bid
     * @return boolean|\libs\db\model
     */
    public function getWithdrawSuccessByUserIdBid($accountId, $bid) {
        if (empty($accountId) || empty($bid)) {
            return false;
        }
        return $this->findBy('`user_id`=\':user_id\' AND `bid`=\':bid\' AND `withdraw_status`=:withdraw_status', '*', [':user_id'=>(int)$accountId, ':bid'=>(int)$bid, ':withdraw_status'=>SupervisionEnum::WITHDRAW_STATUS_SUCCESS]);
    }

    /**
     * 修改提现单状态
     * @param integer $outOrderId 外部订单号
     * @param integer $status 支付状态
     * @param integer $amount 支付金额
     * @param string $remark 提现备注
     * @param string $userLogType 用户资金记录类型
     * @return boolean
     */
    public function orderProcess($outOrderId, $status, $amount, $remark = '', $userLogType = '提现') {
        try {
            $orderInfo = $this->getWithdrawRecordByOutId($outOrderId);
            if ($orderInfo['withdraw_status'] == $status) {
                return true;
            }
            $this->db->startTrans();
            if (empty($outOrderId) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }
            if (empty($orderInfo)) {
                throw new WXException('ERR_CARRY_ORDER_NOT_EXIST');
            }

            $updateData = [];
            $updateData['update_time'] = time();
            $updateData['withdraw_status'] = $status;
            $updateData['remark'] = $remark;

            // 账户ID
            $accountId = $orderInfo['user_id'];
            // 提现金额，单位元
            $withdrawMoney = bcdiv($amount, 100, 2);
            $bizToken = ['orderId' => $outOrderId];
            // 提现
            switch ($status) {
                case SupervisionEnum::WITHDRAW_STATUS_SUCCESS:
                    // 银信通提现成功解冻后面业务做了，这里不做任何操作即可。
                    if($userLogType != '提现至银信通电子账户') {
                        AccountService::changeMoney($accountId, $withdrawMoney, sprintf('%s成功', $userLogType), sprintf('网贷账户%s成功' . $outOrderId, $userLogType), AccountEnum::MONEY_TYPE_LOCK_REDUCE, false, true, 0, $bizToken);
                    }
                    // 如果有白名单限制提现
                    if (!empty($orderInfo['limit_id']) && $orderInfo['type'] == SupervisionEnum::TYPE_LIMIT_WITHDRAW)
                    {
                        // 获取表名
                        $tableName = $this->tableName();
                        $orderStatusString = SupervisionEnum::WITHDRAW_STATUS_PROCESS.','.SupervisionEnum::WITHDRAW_STATUS_NORMAL;
                        $amountSum = $this->db->getOne("SELECT sum(amount) FROM `{$tableName}` WHERE limit_id = {$orderInfo['limit_id']} AND withdraw_status IN ({$orderStatusString}) AND out_order_id != '{$outOrderId}'");
                        if($amountSum == 0)
                        {
                            // 白名单机制收尾
                            AccountLimitService::updateWithdrawLimitRecord($orderInfo['limit_id']);
                        }
                    }
                    (new Zhuge(Zhuge::APP_WEB))->event('网贷账户_提现成功', $orderInfo['user_id'], ['money'=>$withdrawMoney]);
                    (new Zhuge(Zhuge::APP_MOBILE))->event('网贷账户_提现成功', $orderInfo['user_id'], ['money'=>$withdrawMoney]);
                    (new Zhuge(Zhuge::APP_PHWEB))->event('网贷账户_提现成功', $orderInfo['user_id'], ['money'=>$withdrawMoney]);
                    (new Zhuge(Zhuge::APP_PHMOBILE))->event('网贷账户_提现成功', $orderInfo['user_id'], ['money'=>$withdrawMoney]);
                    break;
                case SupervisionEnum::WITHDRAW_STATUS_FAILED:
                    AccountService::changeMoney($accountId, $withdrawMoney, sprintf('%s失败', $userLogType), sprintf('银行受理失败，如有疑问请拨打客服热线 95782。'), AccountEnum::MONEY_TYPE_UNLOCK, false, true, 0, $bizToken);
                    // feature/4919 增加提现放款提现失败邮件告警
                    if ($orderInfo['bid'] != 0)
                    {
                        $failAt = date('Y-m-d H:i:s');
                        // deal表
                        $dealTableName = DealModel::instance()->tableName();
                        $dealInfo = $GLOBALS['db']->getRow("SELECT project_id FROM `{$dealTableName}` WHERE id='{$orderInfo['bid']}'");
                        $oldDealName = getOldDealNameWithPrefix($orderInfo['bid'], $dealInfo['project_id']);
                        $failMessage = "提现编号：{$orderInfo['out_order_id']} 放款标题：{$oldDealName} 放款金额：{$orderInfo['money']} 失败时间：{$failAt}";
                        Alarm::push('supervision_deal_withdraw_fail', '放款提现失败', $failMessage);
                    }
                    //  可提现额度
                    if (!empty($orderInfo['limit_id']) &&$orderInfo['type'] == SupervisionEnum::TYPE_LIMIT_WITHDRAW)
                    {
                        // 增加可提现额度
                        (new AccountLimitService())->addRemainMoney($orderInfo['limit_id'], $orderInfo['amount']);
                    }
                    // 诸葛统计埋点
                    (new Zhuge(Zhuge::APP_PHWEB))->event('提现失败', $orderInfo['user_id'], []);
                    break;
                case SupervisionEnum::WITHDRAW_STATUS_PROCESS:
                    // 限制提现不给用户重新 冻结，已经在同步请求时冻结
                    if ($orderInfo['type'] != SupervisionEnum::TYPE_LIMIT_WITHDRAW) {
                        AccountService::changeMoney($accountId, $withdrawMoney, sprintf('%s申请', $userLogType), sprintf('网贷账户%s申请', $userLogType), AccountEnum::MONEY_TYPE_LOCK, false, true, 0, $bizToken);
                    }
                    break;
                default:
                    throw new WXException('ERR_WITHDRAW_STATUS');
            }

            // 更新附加条件
            $condition = sprintf(" withdraw_status NOT IN (%s) ", implode(', ', SupervisionEnum::$finalStatus));
            //先更新为处理中，才能成功/失败
            if ($orderInfo['type'] != SupervisionEnum::TYPE_TO_CREDIT_ELEC_ACCOUNT && in_array($status, SupervisionEnum::$finalStatus)) {
                $condition = sprintf(" withdraw_status = %d ", SupervisionEnum::WITHDRAW_STATUS_PROCESS);
            }
            $updateResult = $this->updateOrderByOutId($outOrderId, $updateData, $condition);
            if (!$updateResult) {
                throw new WXException('ERR_CARRY_FAILED');
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('UpdateSupervisionWithdrawFailed, code:'.$e->getCode().' message:'.$e->getMessage());
            Alarm::push('supervision_withdraw', '存管提现处理失败', $e->getMessage());
            return false;
        }
    }

    /**
     * 根据标 id，判断标的是否已放款提现
     * 判断标准：放款状态：成功，支付状态：成功
     * @params int $dealId 标的ID
     * @params boolean $isSlave 是否读从库
     * @return boolen
     */
    public function isWithdrawal($dealId, $isSlave = true) {
        $condition = sprintf('bid=\'%d\' AND withdraw_status=\'%d\'', $dealId, SupervisionEnum::WITHDRAW_STATUS_SUCCESS);
        $data = $this->findBy($condition, '*', array(), $isSlave);
        return empty($data) ? false : true;
    }

    /**
     * 获取最新的一条提现成功的记录
     * @param int $userId 用户ID
     * @return boolean
     */
    public function getLastWithdrawInfo($userId) {
        $condition = sprintf("withdraw_status = '%d' AND user_id = '%d' ORDER BY `id` DESC LIMIT 1", SupervisionEnum::WITHDRAW_STATUS_SUCCESS, $userId);
        return $this->findByViaSlave($condition, 'update_time');
    }
}
