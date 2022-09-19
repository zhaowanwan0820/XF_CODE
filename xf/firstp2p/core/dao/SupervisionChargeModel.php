<?php

namespace core\dao;
use libs\common\WXException;
use core\dao\PaymentNoticeModel;
use libs\utils\Site;
use core\dao\UserModel;
use core\dao\DealModel;
use libs\db\Db;
use core\service\ncfph\SupervisionService as PhSupervisionService;

/**
 * 存管账户充值记录表
 **/
class SupervisionChargeModel extends BaseModel {

    // 支付状态
    const PAY_STATUS_NORMAL = 0; // 未处理
    const PAY_STATUS_SUCCESS = 1; // 成功
    const PAY_STATUS_FAILURE = 2; // 失败
    const PAY_STATUS_PROCESS = 3; // 处理中

    static $statusMap = [
        'I' => self::PAY_STATUS_NORMAL,
        'S' => self::PAY_STATUS_SUCCESS,
        'F' => self::PAY_STATUS_FAILURE,
        'AS' => self::PAY_STATUS_PROCESS,
    ];

    /**
     * 普惠库
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 创建充值记录单
     * @param integer $userId 用户ID
     * @param integer $amount 订单金额
     * @param string $outOrderId 外部订单号
     * @return boolean
     */
    public function createOrder($userId, $amount, $outOrderId = '', $platform = PaymentNoticeModel::PLATFORM_SUPERVISION, $dealUserMoney = false) {
        if (empty($userId) || empty($amount)) {
            throw new WXException('ERR_PARAM');
        }
        $insertData = [];
        $insertData['user_id'] = intval($userId);
        $insertData['amount'] = intval($amount);
        $insertData['pay_status'] = self::PAY_STATUS_NORMAL;
        $insertData['platform'] = (int)$platform;
        $insertData['create_time'] = time();
        $insertData['site_id'] = Site::getId();
        if (!empty($outOrderId)) {
            $insertData['out_order_id'] = intval($outOrderId);
        }
        $this->db->autoExecute($this->tableName(), $insertData, 'INSERT');
        return $this->db->affected_rows() > 0 ? true : false;
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
        $outOrderId = intval(trim($outOrderId));
        $this->db->autoExecute($this->tableName(), $updateData, 'UPDATE', " out_order_id = '{$outOrderId}' {$condition}");
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 使用普惠rpc读取订单数据
     */
    public function getChargeRecordByOutId($outOrderId) {
        return PhSupervisionService::chargeGetOrder($outOrderId);
    }

    /**
     * 存管充值
     * @param integer $outOrderId 外部订单号
     * @param integer $status 支付状态
     * @param integer $amount 支付金额
     * @param string $userLogType 用户资金记录类型
     * @return boolean $dealUserMoney 是否处理用户资金
     * @return boolean
     */
    public function orderPaid($outOrderId, $status, $amount, $userLogType = '充值', $dealUserMoney = false) {
        try {
            $orderInfo = $this->getChargeRecordByOutId($outOrderId);
            if ($orderInfo['pay_status'] == $status) {
                return true;
            }
            $this->db->startTrans();
            if (empty($outOrderId) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }
            if (empty($orderInfo)) {
                throw new WXException('ERR_CHARGE_ORDER_NOT_EXSIT');
            }

            $updateData = [];
            $updateData['update_time'] = time();
            $updateData['pay_status'] = $status;

            $userDao = UserModel::instance()->find($orderInfo['user_id']);
            $reportPoint = '';
            // 充值处理
            if ($status == self::PAY_STATUS_SUCCESS) {
                // 不需要处理网信理财账户资金
                if (!$dealUserMoney) {
                    $userDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
                }
                $userLogMemo = '网贷账户充值单'.$outOrderId.'支付成功';
                $bizToken = ['orderId' => $outOrderId];
                $changeMoneyResult = $userDao->changeMoney(bcdiv($amount, 100, 2), $userLogType, $userLogMemo, 0, 0, UserModel::TYPE_MONEY, 0, $bizToken);
                if (!$changeMoneyResult) {
                    throw new WXException('ERR_ORDER_PAID');
                }
                $reportPoint = 'sv_payment_fail';
            }
            else if ($status == self::PAY_STATUS_FAILURE) {
                $reportPoint = 'sv_payment_fail';
            }
            // 更新订单状态
            $updateResult = $this->updateOrderByOutId($outOrderId, $updateData, ' pay_status NOT IN ('.implode(',', [self::PAY_STATUS_SUCCESS, self::PAY_STATUS_FAILURE]).')');
            if (!$updateResult) {
                // 检查订单状态是否已经成功
                $orderInfo = $this->getChargeRecordByOutId($outOrderId);
                if ($orderInfo['pay_status'] != $status) {
                    throw new WXException('ERR_ORDER_PAID');
                }
            }

            // 上报ITIL
            if ($reportPoint !== '') {
                \libs\utils\Monitor::add($reportPoint);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            \libs\utils\PaymentApi::log('存管充值订单'.$outOrderId.' 处理失败,'.$e->getMessage());
        }
    }

    /**
     * 充值记录
     */
    public function getChargeLogs($userId, $ctime = 0, $count = 0, $offset = 0)
    {
        $userId = intval($userId);
        if (empty($userId)) {
            return false;
        }
        $sql = $params = [];
        $tableName = $this->tableName();
        $sql[] = "SELECT * FROM `{$tableName}` WHERE user_id = :user_id";
        $params[':user_id'] = $userId;
        if ($ctime) {
            $sql[] = "and create_time > :ctime";
            $params[':ctime'] = (int)$ctime;
        }
        $sql[] = "order by create_time desc";
        if ($count) {
            $sql[] = "limit :offset, :count";
            $params[':offset'] = (int)$offset;
            $params[':count'] = (int)$count;
        }
        $sqlStr = join(' ', $sql);
        $result = $this->findAllBySql($sqlStr, true, $params);
        return $result;
    }
}
