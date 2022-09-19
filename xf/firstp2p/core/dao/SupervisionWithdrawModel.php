<?php
namespace core\dao;
use libs\common\WXException;
use libs\utils\Site;
use core\dao\UserModel;
use core\dao\DealModel;
use libs\utils\PaymentApi;
use libs\utils\Alarm;
use libs\db\Db;
use core\service\AccountLimitService;
use core\service\ncfph\SupervisionService as PhSupervisionService;


/**
 * 存管账户提现记录
 * 涉及到的函数
 * SupervisionFinanceService/bidElecWithdraw 提现至银信通电子账户
 * SupervisionFinanceService/bidElecWithdrawNotify 提现至银信通电子账户回调
 *
 **/
class SupervisionWithdrawModel extends BaseModel {

    // 提现状态
    const WITHDRAW_STATUS_NORMAL = 0; // 未处理
    const WITHDRAW_STATUS_SUCCESS = 1; // 成功
    const WITHDRAW_STATUS_FAILED = 2; // 失败
    const WITHDRAW_STATUS_PROCESS = 3; // 处理中
    const WITHDRAW_STATUS_INQUEUE = 4; // 自动处理队列

    //终态状态集合
    public static $finalStatus = [self::WITHDRAW_STATUS_SUCCESS, self::WITHDRAW_STATUS_FAILED];

    //提现业务类型
    const TYPE_TO_BANKCARD = 0; //提现至银行卡
    const TYPE_TO_CREDIT_ELEC_ACCOUNT = 1; //提现至银信通电子账户
    const TYPE_ENTRUSTED = 2; //受托提现
    const TYPE_LOCKMONEY = 3; //需要冻结用户资金的提现
    const TYPE_LIMIT_WITHDRAW = 4; //  可提现额度限制提现的提现单
    const TYPE_LIMIT_WITHDRAW_BLACKLIST = 5; //  投资户限制提现的提现单

    public static $withdrawDesc = [
        self::WITHDRAW_STATUS_NORMAL => '未处理',
        self::WITHDRAW_STATUS_PROCESS => '处理中',
        self::WITHDRAW_STATUS_SUCCESS => '提现成功',
        self::WITHDRAW_STATUS_FAILED => '提现失败',
//        self::WITHDRAW_STATUS_INQUEUE => '自动队列',
    ];

    /**
     * 普惠库
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 创建记录单
     * @param integer $userId 用户ID
     * @param integer $amount 订单金额
     * @param string $outOrderId 外部订单号
     * @return boolean
     */
    public function createOrder($userId, $amount, $outOrderId, $bidId = 0, $type = self::TYPE_TO_BANKCARD, $limitId = 0) {
        $tableName = $this->tableName();
        $sql = "SELECT COUNT(*) AS orderInfo FROM `{$tableName}` WHERE out_order_id = '{$outOrderId}'";
        $data = $this->db->getRow($sql);
        if (!empty($data['orderInfo'])) {
           return true;
        }

        try {
            $this->db->startTrans();
            if (empty($userId) || empty($amount) || empty($outOrderId)) {
                throw new WXException('ERR_PARAM');
            }
            $insertData = [];
            $insertData['user_id'] = intval($userId);
            $insertData['amount'] = intval($amount);
            $insertData['out_order_id'] = intval($outOrderId);
            $insertData['withdraw_status'] = self::WITHDRAW_STATUS_NORMAL;
            $insertData['type'] = $type;
            $insertData['create_time'] = time();
            $insertData['site_id'] = Site::getId();
            $insertData['limit_id'] = $limitId;
            if (!empty($bidId)) {
                $insertData['bid'] = intval($bidId);
            }
            // 银信通直接做资产中心的余额冻结操作
            if ($type == self::TYPE_ENTRUSTED || $type == self::TYPE_LOCKMONEY || $type == self::TYPE_LIMIT_WITHDRAW || $type == self::TYPE_LIMIT_WITHDRAW_BLACKLIST) {
                // 受托支付同步更新提现记录状态为处理中
                $insertData['withdraw_status'] = self::WITHDRAW_STATUS_PROCESS;
                $userDao = UserModel::instance()->find($userId);
                $userDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
                $bizToken = ['orderId' => $outOrderId];
                $changeMoneyResult = $userDao->changeMoney(bcdiv($amount, 100, 2), '提现申请', '网贷账户提现申请', 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
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
        return PhSupervisionService::withdrawGetOrder(0 ,0, $outOrderId);
    }

    /**
     * 根据用户ID、标的ID查询提现成功的记录
     * @param int $userId
     * @param int $bid
     * @return boolean|\libs\db\model
     */
    public function getWithdrawSuccessByUserIdBid($userId, $bid) {
        return PhSupervisionService::withdrawGetOrder($userId, $bid, 0);
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

            $withdrawMoney = bcdiv($amount, 100, 2);
            $userDao = UserModel::instance()->find($orderInfo['user_id']);
            $bizToken = ['orderId' => $outOrderId];
            // 提现
            switch ($status) {
                case self::WITHDRAW_STATUS_SUCCESS:
                    $userDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION; //不修改账户余额
                    // 银信通提现成功解冻后面业务做了，这里不做任何操作即可。
                    if($userLogType != '提现至银信通电子账户') {
                        $userDao->changeMoney($withdrawMoney, sprintf('%s成功', $userLogType), sprintf('网贷账户%s成功' . $outOrderId, $userLogType), 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY, 0, $bizToken);
                    }
                    // 如果有白名单限制提现
                    if (!empty($orderInfo['limit_id']) && $orderInfo['type'] == self::TYPE_LIMIT_WITHDRAW)
                    {
                        $orderStatusString = self::WITHDRAW_STATUS_PROCESS.','.self::WITHDRAW_STATUS_NORMAL;
                        $amountSum = $this->db->getOne("SELECT sum(amount) FROM firstp2p_supervision_withdraw WHERE limit_id = {$orderInfo['limit_id']} AND withdraw_status IN ({$orderStatusString}) AND out_order_id != '{$outOrderId}'");
                        if($amountSum == 0)
                        {
                            // 白名单机制收尾
                            AccountLimitService::updateWithdrawLimitRecord($orderInfo['limit_id'], true);
                        }
                    }
                    break;
                case self::WITHDRAW_STATUS_FAILED:
                    $userDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION; //不修改账户余额
                    $userDao->changeMoney(-$withdrawMoney, sprintf('%s失败', $userLogType), sprintf('银行受理失败，如有疑问请拨打客服热线 95782。'), 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
                    // feature/4919 增加提现放款提现失败邮件告警
                    if ($orderInfo['bid'] != 0)
                    {
                        $failAt = date('Y-m-d H:i:s');
                        $dealInfo = $GLOBALS['db']->getRow("SELECT project_id FROM firstp2p_deal WHERE id='{$orderInfo['bid']}'");
                        $oldDealName = getOldDealNameWithPrefix($orderInfo['bid'], $dealInfo['project_id']);
                        $failMessage = "提现编号：{$orderInfo['out_order_id']} 放款标题：{$oldDealName} 放款金额：{$orderInfo['money']} 失败时间：{$failAt}";
                        Alarm::push('supervision_deal_withdraw_fail', '放款提现失败', $failMessage);
                    }
                    //  可提现额度
                    if (!empty($orderInfo['limit_id']) &&$orderInfo['type'] == self::TYPE_LIMIT_WITHDRAW)
                    {
                        // 增加可提现额度
                        (new AccountLimitService())->addRemainMoney($orderInfo['limit_id'], $orderInfo['amount'], true);
                    }
                    break;
                case self::WITHDRAW_STATUS_PROCESS:
                    $userDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION; //不修改账户余额
                    // 限制提现不给用户重新 冻结，已经在同步请求时冻结
                    if ($orderInfo['type'] != self::TYPE_LIMIT_WITHDRAW) {
                        $userDao->changeMoney($withdrawMoney, sprintf('%s申请', $userLogType), sprintf('网贷账户%s申请', $userLogType), 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
                    }
                    break;
                default:
                    throw new WXException('ERR_WITHDRAW_STATUS');
            }

            // 更新附加条件
            $condition = sprintf(" withdraw_status NOT IN (%s) ", implode(', ', self::$finalStatus));
            //先更新为处理中，才能成功/失败
            if ($orderInfo['type'] != self::TYPE_TO_CREDIT_ELEC_ACCOUNT && in_array($status, self::$finalStatus)) {
                $condition = sprintf(" withdraw_status = %d ", self::WITHDRAW_STATUS_PROCESS);
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
}
