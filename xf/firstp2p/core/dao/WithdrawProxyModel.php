<?php
namespace core\dao;

use libs\utils\PaymentApi;
use NCFGroup\Commom\Library\Idworker;

class WithdrawProxyModel extends BaseModel {

    // 业务类型
    const BIZ_TYPE_REPAY_PRINCIPAL      = 1;    // 本金
    const BIZ_TYPE_REPAY_INTEREST       = 2;    // 利息
    const BIZ_TYPE_CREDITLOAN_FEE       = 3;    // 银信通服务费
    const BIZ_TYPE_CREDITLOAN_PRINCIPAL = 4;    // 银信通本金
    const BIZ_TYPE_CREDITLOAN_INTEREST  = 5;    // 银信通利息
    const BIZ_TYPE_CREDITLOAN_RETURN    = 6;    // 银信通返还费用
    const BIZ_TYPE_SPEEDLOAN            = 7;    // 速贷还款
    const BIZ_TYPE_SPEEDLOAN_FEE        = 8;    // 速贷服务费
    const BIZ_TYPE_SPEEDLOAN_RETURN     = 9;   // 速贷服返还费用
    const BIZ_TYPE_DEBITION             = 10;   // 偿还债权

    // 订单状态
    const ORDER_STATUS_INIT     = 0;    // 未发送
    const ORDER_STATUS_SENDING  = 1;    // 处理中
    const ORDER_STATUS_SUCCESS  = 2;    // 打款成功
    const ORDER_STATUS_FAILURE  = 3;    // 打款失败
    const ORDER_STATUS_FALLBACK = 4;    // 已经重新代发

    static $orderStatusDesc = array(
        self::ORDER_STATUS_INIT     => '等待发送',
        self::ORDER_STATUS_SENDING  => '等待通知',
        self::ORDER_STATUS_SUCCESS  => '成功',
        self::ORDER_STATUS_FAILURE  => '失败',
        self::ORDER_STATUS_FALLBACK => '重新代发',
    );

    // 收款账户类型
    const USER_TYPE_PERSON      = 1; // 对私用户类型
    const USER_TYPE_PUBLIC      = 2; // 对公用户类型
    static $userTypeDesc        = array(
        self::USER_TYPE_PERSON  => '对私',
        self::USER_TYPE_PUBLIC  => '对公',
    );

    // 特定收款账户名称
    const USER_CREDITLOAN       = 'creditloan'; // 银信通
    const USER_SPEEDLOAN        = 'speedloan'; // 速贷

    // 通知业务结果
    const NOTIFY_SERVICE_SUCCESS= 1;
    const NOTIFY_SERVICE_WAIT   = 0;

    // 业务类型描述
    static $bizTypeDesc = array(
        self::BIZ_TYPE_REPAY_PRINCIPAL      => '本金',
        self::BIZ_TYPE_REPAY_INTEREST       => '利息',
        self::BIZ_TYPE_CREDITLOAN_PRINCIPAL => '银信通本金',
        self::BIZ_TYPE_CREDITLOAN_INTEREST  => '银信通利息',
        self::BIZ_TYPE_CREDITLOAN_RETURN    => '银信通解冻本金',
        self::BIZ_TYPE_CREDITLOAN_FEE       => '银信通服务费',
        self::BIZ_TYPE_SPEEDLOAN            => '速贷还款本息',
        self::BIZ_TYPE_SPEEDLOAN_RETURN     => '速贷解冻本金',
        self::BIZ_TYPE_SPEEDLOAN_FEE        => '速贷服务费',
        self::BIZ_TYPE_DEBITION             => '偿还债权',
    );

    /**
     * 通知业务结果队列
     */
    public function popNotify($pidCount, $pidOffset)
    {
        $startTime = microtime(true);
        $time = time();
        $condition = " notify_service_success = ".self::NOTIFY_SERVICE_WAIT."  AND notify_retry_counter < 10 AND order_status = ".self::ORDER_STATUS_SUCCESS." AND next_notify_time <= {$time}";
        $sql = "SELECT * FROM firstp2p_withdraw_proxy WHERE id%{$pidCount}={$pidOffset} AND {$condition}";
        // 因为主从同步延时比较大，改走主库
        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result))
        {
            //echo 'pop empty' .PHP_EOL;
            return array();
        }
        $data = $result;
        if ($data['notify_service_success'] == self::NOTIFY_SERVICE_SUCCESS)
        {
            PaymentApi::log("WithdrawProxyWorker popNotify conflict. id:{$data['id']}, time:".(microtime(true) - $startTime));
            return false;
        }
        $this->updateNotifyRetryCounter($data['id'], $data['notify_retry_counter']);

        PaymentApi::log("WithdrawProxyWorker popNotify success. id:{$data['id']}, time:".(microtime(true) - $startTime));
        return $data;

    }

    /**
     * 请求支付无响应重试队列
     */
    public function popRetry ($pidCount, $pidOffset)
    {
        return $this->pop($pidCount, $pidOffset, true);
    }

    /**
     *  批量读取需要执行的数据
     */
    public function pop($pidCount, $pidOffset, $isRetry = false)
    {
        // 动态提取三天前转账记录的id
        $startTime = microtime(true);
        $fromTime = strtotime('-2 days');
        $now = time();
        $retryTime = $now - 300;
        $retryMaxTimes = 120;
        $condition = '';
        if (!$isRetry) {
            $condition = sprintf(' create_time >= %d AND order_status = %d LIMIT 1', $fromTime, self::ORDER_STATUS_INIT);
        } else {
            $condition = sprintf(' create_time >= %d AND create_time < %d AND order_status = %d AND next_retry_time <= %d AND retry_counter < %d LIMIT 1', $fromTime, $retryTime, self::ORDER_STATUS_SENDING, $now, $retryMaxTimes);
        }
        $sql = "SELECT * FROM firstp2p_withdraw_proxy WHERE id%{$pidCount}={$pidOffset} AND {$condition}";
        //echo $sql.PHP_EOL;
        // 因为主从同步延时比较大，改走主库
        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result))
        {
            //echo 'pop empty' .PHP_EOL;
            return array();
        }
        $data = $result;
        if ($isRetry == false && $data['order_status'] == self::ORDER_STATUS_INIT && $this->setQueueStatus($data['id'], self::ORDER_STATUS_SENDING) === false)
        {
            PaymentApi::log("WithdrawProxyWorker pop conflict. id:{$data['id']}, time:".(microtime(true) - $startTime));
            return false;
        }
        if ($isRetry)
        {
            $this->updateRetryCounter($data);
        }

        PaymentApi::log("WithdrawProxyWorker pop success. id:{$data['id']}, time:".(microtime(true) - $startTime));
        return $data;
    }

    /**
     * 设置队列处理状态
     */
    private function setQueueStatus($id, $req_status)
    {
        $this->db->query("UPDATE firstp2p_withdraw_proxy SET order_status = {$req_status} WHERE id = '{$id}'");
        return $this->db->affected_rows() == 1 ? true : false;
    }

    /**
     * 更新业务通知状态
     */
    function updateNotifySuccess($id)
    {
        $this->db->query("UPDATE firstp2p_withdraw_proxy SET notify_service_success = 1  WHERE id = '{$id}'");
        return $this->db->affected_rows() == 1 ? true : false;
    }


    /**
     * 更新重试计数器
     */
    public function updateRetryCounter($data)
    {
        $nextRetryTime = time() + 60 * $data['retry_counter'];
        $this->db->query("UPDATE firstp2p_withdraw_proxy SET retry_counter = retry_counter + 1,next_retry_time = {$nextRetryTime}  WHERE id = '{$data['id']}'");
        return $this->db->affected_rows() == 1 ? true : false;
    }

    public function resetNotifyCounter($id)
    {
        $this->db->query("UPDATE firstp2p_withdraw_proxy SET notify_retry_counter = 0 WHERE id = '{$id}'");
        return $this->db->affected_rows() == 1 ? true : false;
    }

    /**
     * 更新通知计数器
     */
    public function updateNotifyRetryCounter($id, $retryTime = 1)
    {
        // 120s重试一次
        if ($retryTime == 0)
        {
            $retryTime = time() + 30;
            $this->db->query("UPDATE firstp2p_withdraw_proxy SET notify_retry_counter = notify_retry_counter + 1, next_notify_time = {$retryTime}  WHERE id = '{$id}'");
        } else {
            $nextNotifyTime = time();
            $this->db->query("UPDATE firstp2p_withdraw_proxy SET notify_retry_counter = notify_retry_counter + 1, next_notify_time = {$nextNotifyTime} + 60*{$retryTime}  WHERE id = '{$id}'");
        }
        return $this->db->affected_rows() == 1 ? true : false;
    }

    /**
     * 更新记录
     */
    public function updateRecord($data)
    {
        foreach ($data as $field => $val)
        {
            $this->{$field} = addslashes($val);
        }
        return $this->update();
    }


    /**
     * 统计某个项目批次中指定代发类型的代发成功总额
     * @param integer $batchNo 项目批次号
     * @param $bizTypes 业务类型集合
     * @return integer 统计金额, 单位分
     */
    public static function sumByMerchantBatchNo($batchNo, $bizTypes = [])
    {
        $condition = '';
        if (!empty($bizTypes) && is_array($bizTypes))
        {
            $condition = ' AND biz_type IN ('.implode(',', $bizTypes).')';
        }
        $status = self::ORDER_STATUS_SUCCESS;
        return \libs\db\Db::getInstance('firstp2p', 'slave')->getOne("SELECT SUM(amount) AS sumAmount FROM firstp2p_withdraw_proxy WHERE merchant_batch_no = '{$batchNo}' AND order_status = '{$status}' {$condition} ");
    }


    /**
     * 统计重试笔数
     */
    public static function countRedoTimes($merchantNo, $merchantNoSeq, $needOriginal = false)
    {
        $condition = '';
        if ($needOriginal)
        {
            $condition = ' AND fallback_counter = 0 ';
        }
        return \libs\db\Db::getInstance('firstp2p', 'slave')->getOne("SELECT count(*) FROM firstp2p_withdraw_proxy WHERE merchant_no = {$merchantNo} AND merchant_no_seq = {$merchantNoSeq}{$condition}");
    }


    /**
     * 判断代发记录是否存在
     * @param array WithdrawProxyModel RowData
     */
    public static function isWithdrawExists($withdrawInfo)
    {
        $rowCount = self::countRedoTimes($withdrawInfo['merchant_no'], $withdrawInfo['merchant_no_seq'], true);
        return $rowCount >= 1 ? true : false;
    }

    /**
     * 根据request no 取处于终态的订单信息
     */
    public static function getOrderInfo($requestNo)
    {
        $sql = "SELECT * FROM firstp2p_withdraw_proxy WHERE request_no = '{$requestNo}'
            AND order_status IN (".implode(',', [self::ORDER_STATUS_SUCCESS, self::ORDER_STATUS_FAILURE]).")";
        return \libs\db\Db::getInstance('firstp2p', 'slave')->getRow($sql);

    }

    /**
     * 读取指定日期内的所有终态的代发记录
     */
    public static function getRecordId($merchantId, $date)
    {
        $timeStart = strtotime($date);
        if ($timeStart  == 0)
        {
            $timeStart = strtotime(date('Ymd'));
        }
        $timeEnd = $timeStart + 86400;
        $sql = "SELECT max(id) AS endRecordId, min(id) as beginRecordId FROM firstp2p_withdraw_proxy WHERE create_time >= {$timeStart} AND create_time <= {$timeEnd} AND order_status IN (2,3) AND merchant_id = '{$merchantId}'";
        $data = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow($sql);
        return $data;
    }


    /**
     * 根据id范围读取代发记录
     */
    public static function getRecordList($startId, $endId, $fieldsList = '*')
    {
        $startId = intval($startId);
        $endId = intval($endId);
        if ($endId - $startId > 100000)
        {
            throw new \Exception ('id区间大于1w条记录,请重新切片');
        }
        $orderStatusCondition = implode(',', [self::ORDER_STATUS_SUCCESS, self::ORDER_STATUS_FAILURE]);
        $sql = "SELECT {$fieldsList} FROM firstp2p_withdraw_proxy WHERE id >= $startId AND id < $endId AND order_status IN ($orderStatusCondition)";
        return \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($sql);
    }
}
