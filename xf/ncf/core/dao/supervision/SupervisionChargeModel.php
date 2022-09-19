<?php
namespace core\dao\supervision;

use core\enum\SupervisionEnum;
use NCFGroup\Common\Library\Idworker;
use libs\common\WXException;
use libs\utils\Site;
use libs\utils\Monitor;
use libs\utils\PaymentApi;
use core\enum\PaymentEnum;
use core\enum\AccountEnum;
use core\dao\BaseModel;
use core\service\account\AccountService;

/**
 * 存管账户充值记录表
 **/
class SupervisionChargeModel extends BaseModel {

    // 充值来源 - 来自基金赎回
    const PLATFORM_FUND_REDEEM = 14;

    /**
     * 创建充值记录单
     * @param integer $accountId 账户ID
     * @param integer $amount 订单金额
     * @param string $outOrderId 外部订单号
     * @return boolean
     */
    public function createOrder($accountId, $amount, $outOrderId = '', $platform = PaymentEnum::PLATFORM_SUPERVISION) {
        if (empty($accountId) || empty($amount)) {
            throw new WXException('ERR_PARAM');
        }
        $insertData = [];
        $insertData['user_id'] = intval($accountId);
        $insertData['amount'] = intval($amount);
        $insertData['pay_status'] = SupervisionEnum::PAY_STATUS_NORMAL;
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

    public function getChargeRecordByOutId($outOrderId) {
        if (empty($outOrderId)) {
            return false;
        }
        $outOrderId = intval(trim($outOrderId));
        // 获取表名
        $tableName = $this->tableName();
        return $this->findBySql("SELECT * FROM `{$tableName}` WHERE out_order_id = '{$outOrderId}'"); //查询主库
    }

    public function getUserLastCharge($userId)
    {
        if (empty($userId)) {
            return false;
        }
        // 获取表名
        $tableName = $this->tableName();
        $sql = "SELECT * FROM `{$tableName}` WHERE user_id = '{$userId}' AND pay_status = ".SupervisionEnum::PAY_STATUS_SUCCESS." ORDER BY id DESC LIMIT 1";
        $result = $this->findBySql($sql); //查询主库
        return $result;
    }

    /**
     * 存管充值
     * @param integer $outOrderId 外部订单号
     * @param integer $status 支付状态
     * @param integer $amount 支付金额
     * @param string $userLogType 用户资金记录类型
     * @return boolean
     */
    public function orderPaid($outOrderId, $status, $amount, $userLogType = '充值') {
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

            // 存管回调回来的是账户ID
            $accountId = $orderInfo['user_id'];
            $reportPoint = '';
            // 充值处理
            if ($status == SupervisionEnum::PAY_STATUS_SUCCESS) {
                // 存管充值成功回调之后的处理
                $userLogMemo = '网贷账户充值单'.$outOrderId.'支付成功';
                // 生成订单号
                $bizToken = ['orderId' => $outOrderId];
                $changeMoneyResult = AccountService::changeMoney($accountId, bcdiv($amount, 100, 2), $userLogType, $userLogMemo, AccountEnum::MONEY_TYPE_INCR, false, true, 0, $bizToken);
                if (!$changeMoneyResult) {
                    throw new WXException('ERR_ORDER_PAID');
                }
                $reportPoint = 'sv_payment_fail';
            }
            else if ($status == SupervisionEnum::PAY_STATUS_FAILURE) {
                $reportPoint = 'sv_payment_fail';
            }
            // 更新订单状态
            $updateResult = $this->updateOrderByOutId($outOrderId, $updateData, ' pay_status NOT IN ('.implode(',', [SupervisionEnum::PAY_STATUS_SUCCESS, SupervisionEnum::PAY_STATUS_FAILURE]).')');
            if (!$updateResult) {
                // 检查订单状态是否已经成功
                $orderInfo = $this->getChargeRecordByOutId($outOrderId);
                if ($orderInfo['pay_status'] != $status) {
                    throw new WXException('ERR_ORDER_PAID');
                }
            }

            // 上报ITIL
            if ($reportPoint !== '') {
                Monitor::add($reportPoint);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('存管充值订单'.$outOrderId.' 处理失败,' . $e->getMessage());
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
        // 获取表名
        $tableName = $this->tableName();
        $sql = $params = [];
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

    public function getRecentList($userId, $offset, $count)
    {
        $userId = intval($userId);
        if (empty($userId)) {
            return false;
        }
        // 获取表名
        $tableName = $this->tableName();
        $sql = $params = [];
        $sql[] = "SELECT * FROM `{$tableName}` WHERE user_id = :user_id";
        $params[':user_id'] = $userId;
        $sql[] = "order by create_time desc";
        if ($count) {
            $sql[] = "limit :offset, :count";
            $params[':offset'] = (int)$offset;
            $params[':count'] = (int)$count;
        }
        $sqlStr = join(' ', $sql);
        $list = $this->findAllBySql($sqlStr, true, $params);
        if (is_array($list)) {
            foreach ($list as $k => $item) {
                $status_cn = '未付款';
                if ($item['pay_status'] == 2) {
                    // $status_cn = $item['amount_limit'] == self::AMOUNT_LIMIT_BIG ? '银行处理中' : '付款中';
                    $status_cn = '银行处理中';
                }
                else if ($item['pay_status'] == 1) {
                    $status_cn = '付款成功';
                }else if ($item['pay_status'] == 3){
                    $status_cn = '付款失败';
                }
                $list[$k]['status_cn'] = $status_cn;
//                 deal order id
//                 $deal_order = DealOrderModel::instance()->find($item['order_id'], '*', true);
//                 if (!empty($deal_order)) {
//                      $list[$k]['notice_sn'] = $deal_order->order_sn;
//                 }
            }
        }
        return $list;
    }

    /**
     * 获取最早的一条充值成功的记录
     * @param int $userId 用户ID
     * @param int $todayStamp
     * @return boolean
     */
    public function getEarlyChargeInfo($userId, $todayStamp = 0) {
        $condition = sprintf("pay_status = '%d' AND user_id = '%d'", SupervisionEnum::PAY_STATUS_SUCCESS, $userId);
        if (!empty($todayStamp))
        {
            $condition .= sprintf(" AND create_time >= '%d'", $todayStamp);
        }
        return $this->findByViaSlave($condition, 'MIN(id) AS minId,create_time,update_time,amount');
    }

    /**
     * 统计用户当天网贷充值总金额
     * @param integer $accountId 账户id
     * @return floatval 充值金额
     */
    public function sumUserChargeAmountToday($accountId)
    {
        $now = time();
        $todayTimeBegins = $now - $now % 86400;
        $todayTimeEnds = $todayTimeBegins + 86400;
        $sql = "SELECT sum(amount) FROM firstp2p_supervision_charge WHERE user_id = '{$accountId}' AND update_time >= '{$todayTimeBegins}' AND update_time < '{$todayTimeEnds}' AND pay_status = 1";
        $result = $this->db->getOne($sql);
        return bcdiv($result, 100, 2);
    }

    /**
     * 统计用户当天网贷线上充值总金额
     * @param integer $accountId 账户id
     * @return floatval 充值金额
     */
    public function sumUserOnlineChargeAmountToday($accountId)
    {
        $todayTimeBegins = strtotime(date('Y-m-d'));
        $todayTimeEnds = $todayTimeBegins + 86400;
        $sql = "SELECT sum(amount) FROM firstp2p_supervision_charge WHERE user_id = '{$accountId}' AND update_time >= '{$todayTimeBegins}' AND update_time < '{$todayTimeEnds}' AND pay_status = 1";
        $sql .= sprintf(' AND platform IN (%s) ', implode(',', PaymentEnum::$onlinePlatform));
        $result = $this->db->getOne($sql);
        return bcdiv($result, 100, 2);
    }

}
