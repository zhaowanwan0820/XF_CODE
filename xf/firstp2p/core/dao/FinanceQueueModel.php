<?php
/**
 * DB queue class file.
 * 资金托管专用队列
 *
 * @author 张若识 <zhangruoshi@ucfgroup.com>
 **/
namespace core\dao;
use core\dao\FinanceDetailLogModel;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Curl;

class FinanceQueueModel extends BaseModel
{

    const STATUS_NORMAL = 0;//未处理状态
    const STATUS_SUCCESS =1;//处理完成状态
    const STATUS_ERROR = 2;//处理失败
    const STATUS_SENDING = 3; //处理中

    const REQ_STATUS_NORMAL = 0;//请求未发送
    const REQ_STATUS_SUCCESS = 1;//请求已发送

    const PRIORITY_DEAL = 100;  //放款优先级
    const PRIORITY_HIGH = 10;  //高优先级
    const PRIORITY_NORMAL = 0;  //普通优先级

    const REQ_STATUS_PROCESSED = '30006';

    /**
     * 支付队列业务类型-还款(DealRepayModel::repay)
     * @var int
     */
    const PAYQUEUE_BIZTYPE_1 = 1;

    /**
     * 支付队列业务类型-红包
     * @var int
     */
    const PAYQUEUE_BIZTYPE_2 = 2;

    /**
     * 支付队列业务类型-返利(CouponLogService::payOut)
     * @var int
     */
    const PAYQUEUE_BIZTYPE_3 = 3;

    /**
     * 支付队列业务类型-放款(DealPrepayService::prepay)
     * @var int
     */
    const PAYQUEUE_BIZTYPE_4 = 4;

    /**
     * 支付队列业务类型-转账(TransferService::_sync)
     * @var int
     */
    const PAYQUEUE_BIZTYPE_5 = 5;

    /**
     * 支付队列业务类型-即付投资(DealService::transferBidJF)
     * @var int
     */
    const PAYQUEUE_BIZTYPE_6 = 6;

    /**
     * 支付队列业务类型-即付回款(DealService::transferRepayJF)
     * @var int
     */
    const PAYQUEUE_BIZTYPE_7 = 7;

    /**
     * 支付队列业务类型-易宝充值(YeepayPaymentService::_transferYeepayPayerMoney)
     * @var int
     */
    const PAYQUEUE_BIZTYPE_8 = 8;

    /**
     * 支付队列业务类型-多投宝申购
     * @var int
     */
    const PAYQUEUE_BIZTYPE_11 = 11;

    /**
     * 支付队列业务类型-多投宝赎回
     * @var int
     */
    const PAYQUEUE_BIZTYPE_12 = 12;

    /**
     * 支付队列业务类型-多投宝付息
     * @var int
     */
    const PAYQUEUE_BIZTYPE_13 = 13;

    /**
     * 支付队列业务类型-多投宝结息
     * @var int
     */
    const PAYQUEUE_BIZTYPE_14 = 14;

    /**
     * 支付队列业务类型-多投宝收取管理费
     * @var int
     */
    const PAYQUEUE_BIZTYPE_15 = 15;

    /**
     * 支付队列业务类型-第三方转账
     * @var int
     */
    const PAYQUEUE_BIZTYPE_16 = 16;

    /**
     * 支付队列业务类型-黄金标放款
     * @var int
     */
    const PAYQUEUE_BIZTYPE_GOLD_GRANT = 17;

    /**
     * 支付队列业务类型-黄金变现
     * @var int
     */
    const PAYQUEUE_BIZTYPE_GOLD_WITHDRAW = 18;

    /**
     * 支付队列业务类型 黄金返利结息
     */
    const PAYQUEUE_BIZTYPE_GOLD_COUPON_LOG = 19;

    /**
     * 支付队列类型需要同步大帅的
     * \NCFGroup\Protos\Ptp\Enum\PayQueueEnum
     */

    //操作类型，必须在此定义过的类型才能入队列
    public static $queue_types = array(
        'register' => '注册开户',
        'transfer' => '转账',
    );

    /**
     * 支持大批次转账数据切分处理,防止支付挂掉
     */
    public function push($data, $type, $priority = 0)
    {
        $result = array();
        if (is_array($data['orders']))
        {
            $sliceOrders = array_chunk($data['orders'], 2000);
            if (is_array($sliceOrders))
            {
                foreach ($sliceOrders as $sliceOrder)
                {
                    $pushData = array();
                    $pushData['orders'] = $sliceOrder;
                    $ret = $this->_pushData($pushData, $type, $priority);
                    if (is_array($ret))
                    {
                        $result = array_merge($result, $ret);
                    }
                }
            }

            \libs\utils\Monitor::add('TRANSFER_COUNT', count($data['orders']));
        }

        return $result;
    }

    /**
     * 数据push入队列，一次一条
     * @param array $data 队列数据，队列消费者处理任务时用到的数据
     * $info = array( 'orders' => $data);
     * @param string $type 资金托管业务类型
     * @return int 1成功，0失败
     */
    private function _pushData($data, $type, $priority = 0){
        $status =  $this->isEnable();
        if(!$status){
            return true;
        }
        if(!isset(self::$queue_types[$type]) || empty($data['orders'])) {
            \libs\utils\Alarm::push('payment', 'transfer', '转账数据写入队列失败，参数不齐. type:' . $type. 'data:' . var_export($data));
            return 0;
        }
        if (isset($data['orders']) && is_array($data['orders'])) {
            $idx = 1;
            $rebuildIndex = false;
            foreach ($data['orders'] as $k =>$item) {
                //转账付款收款同一人不处理
                if ($item['payerId'] == $item['receiverId']) {
                    unset($data['orders'][$k]);
                    $rebuildIndex = true;
                    continue;
                }
                $data['orders'][$k]['cate'] = $item['outOrderId'];
                $data['orders'][$k]['outOrderId'] =  ''; //$this->_createOrderId($idx ++);
            }
            // 重建索引
            if ($rebuildIndex) {
               $data['orders'] = array_values($data['orders']);
            }
        }
        if (empty($data['orders'])) {
            return 1;
        }

        $result = array();

        // 分发到外一个详情表里
        $this->db->startTrans();
        try {
            // 一个批次内的订单数据
            $batchIds = array();
            // write detail log
            if (is_array($data)) {
                $financeDetailLog = FinanceDetailLogModel::instance();
                foreach ($data['orders'] as $k => $item) {
                    $now = time();
                    $financeDetailLog->setRow(array_merge(array('create_time' => $now), $item )) ;
                    $res = $financeDetailLog->insert();
                    if(!$res) {
                       throw new Exception('对账详情入详情数据表失败'  . json_encode($item));
                    }
                    $last_insert_id = $GLOBALS['db']->insert_id();
                    $batchIds[] = $last_insert_id;
                    $result[] = $last_insert_id;
                    $data['orders'][$k]['outOrderId'] = $last_insert_id;
                    $data['orders'][$k]['createTime'] = $now;
                    $GLOBALS['db']->autoExecute('firstp2p_finance_detail_log', array('outOrderId' => $last_insert_id), 'UPDATE', " id = '{$last_insert_id}' ");
                }
            }
            $data['orders'] = json_encode($data['orders']);
            $content = serialize($data);
            $sign = md5($content);//签名
            //重复的任务不再写入队列
            $condition = sprintf(" `sign`='%s' ",$sign);
            $same = $this->findBy($condition, 'id');
            if($same)  {
                \libs\utils\Alarm::push('payment', 'transfer', '转账数据写入队列失败,相同的业务请求已经存在。' . $content);
                throw new \Exception('');
            }
            $queue_data = array(
                'create_time' => get_gmtime(),
                'content' => $content,
                'type' => $type,
                'sign' => $sign,
                'status' => self::STATUS_NORMAL,
                'priority' => $priority,
                'req_status'=> self::REQ_STATUS_NORMAL,
            );


            $this->setRow($queue_data);
            $insertResult = $this->insert();
            if(!$insertResult) {
                 throw new \Exception('转账数据写入队列失败'  . json_encode($item));
            }
            $last_insert_id = $GLOBALS['db']->insert_id();
            // 回填批次号
            $batchId = implode(',', $batchIds);
            $batchUpdate = "UPDATE firstp2p_finance_detail_log SET preBatchId = '{$last_insert_id}' WHERE id IN ({$batchId})";
            PaymentApi::log('Update transfer batch id : '.$batchUpdate);
            $GLOBALS['db']->query($batchUpdate);
            PaymentApi::log('update result :affect '.$GLOBALS['db']->affected_rows().' rows');
            $affected_rows = $GLOBALS['db']->affected_rows();
            if ($affected_rows <= 0) {
                throw new \Exception('转账数据写入队列失败');
            }

            // COMMIT
            $this->db->commit();
        }
        catch (\Exception $e) {
            // ROLLBACK
            $this->db->rollback();
            PaymentApi::log('FinanceQueueModel::push failed, '.$e->getMessage());
            \libs\utils\Alarm::push('payment', 'transfer', $e->getMessage());
            return false;
        }
        return $result;
    }

    private function isEnable(){
        if(app_conf('PAYMENT_ENABLE')){
           return true;
        }
        else{
           return false;
        }
    }

    /**
     * POP一条数据，原子操作
     */
    public function pop($pidCount, $pidOffset)
    {
        // 动态提取三天前转账记录的id
        $time = strtotime('-2 days') - 28800;
        $condition = sprintf(' create_time >= '.$time.' AND status=%s AND priority>0  ORDER BY priority DESC LIMIT 1', self::STATUS_NORMAL);
        $sql = "SELECT * FROM firstp2p_finance_queue WHERE id%{$pidCount}={$pidOffset} AND {$condition}";
        // 因为主从同步延时比较大，改走主库
        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result))
        {
            PaymentApi::log("PaymentapiWorker pop empty.");
            return array();
        }

        $data = $result;
        $data['content'] = unserialize($data['content']);
        $data['content']['preBatchId'] = $data['id'];

        if ($this->setQueueStatus($data['id'], self::STATUS_SENDING, self::STATUS_NORMAL) === false)
        {
            PaymentApi::log("PaymentapiWorker pop conflict. id:{$data['id']}, time:".microtime(true));
            return false;
        }

        PaymentApi::log("PaymentapiWorker pop success. id:{$data['id']}, time:".microtime(true));
        return $data;
    }


    /**
     * 批量pop数据并打包成一个批次
     */
    public function popBatch($pidCount, $pidOffset, $batchCount = 200)
    {
        $time = strtotime('-2 days') - 28800;
        // 只合并普通优先级转账记录和还款转账记录,防止出现大批次合并
        $condition = sprintf(' create_time >= '.$time.' AND status=%s AND priority=0 ORDER BY priority DESC LIMIT '.$batchCount, self::STATUS_NORMAL);
        $sql = "SELECT * FROM firstp2p_finance_queue WHERE id%{$pidCount}={$pidOffset} AND {$condition}";
        // 因为主从同步延时比较大，改走主库
        $data = $GLOBALS['db']->getAll($sql);
        $ids = array();
        $reqData['content'] = array();
        $reqData['content']['preBatchId'] = md5(posix_getpid().microtime(true).mt_rand());
        if (!empty($data) && is_array($data)) {
            foreach ($data as $k => $row) {
                $d = unserialize($row['content']);
                $ids[] = $row['id'];
                $orders = json_decode($d['orders'], 1);
                if (is_array($orders)) {
                    foreach ($orders as $order) {
                        $reqData['content']['orders'][] = $order;
                    }
                }
            }
        }
        else
        {
            PaymentApi::log("PaymentapiBatchWorker popBatch empty.");
            return array();
        }
        // 更新批次为处理中状态
        $GLOBALS['db']->query('UPDATE firstp2p_finance_queue SET status="'.self::STATUS_SENDING.'" WHERE id IN ('.implode(',',$ids).')');
        // 合并批次数据
        $reqData['content']['orders'] = json_encode($reqData['content']['orders']);
        $reqData['ids'] = $ids;
        PaymentApi::log('PaymentapiBatchWorker popBatch success: time:'.microtime(true).' ids:'.implode(',', $ids));
        return $reqData;
    }

    /**
     * POP一条重试数据
     */
    public function popRetry()
    {
        $time = get_gmtime();
        $condition = sprintf("req_status=%s AND `status`=%s AND (req_times>=1 AND req_times<11) AND next_req_time<=%s AND create_time>%s ORDER BY priority DESC, id ASC LIMIT 500", self::REQ_STATUS_NORMAL, self::STATUS_SENDING, $time, $time - 86400 * 7);
        $result = $GLOBALS['db']->get_slave()->getAll("SELECT * FROM firstp2p_finance_queue WHERE {$condition}");
        if (empty($result))
        {
            PaymentApi::log("PaymentapiWorkerRetry pop empty.");
            return array();
        }

        foreach ($result as $key => $data) {
            $result[$key]['content'] = unserialize($result[$key]['content']);
            $result[$key]['content']['preBatchId'] = $result[$key]['id'];
        }

        PaymentApi::log("PaymentapiWorkerRetry pop success. count:".count($result));
        return $result;
    }

    /**
     * 更新队列状态
     */
    private function setQueueStatus($id, $status, $statusExpect = false)
    {
        $sql = "UPDATE firstp2p_finance_queue SET status='{$status}' WHERE id='{$id}'";
        $sql .= $statusExpect !== false ? " AND status='{$statusExpect}'" : '';
        $sql .= ' LIMIT 1';

        return $this->updateRows($sql) == 1 ? true : false;
    }

    /**
     * 更新请求状态
     * @param $id int 记录id
     * @param $status string 状态(NORMAL|SUCCESS)
     * @return boolen
     */
    public function setReqStatus($id, $status, $requestSuccess)
    {
        $status_name = 'self::REQ_STATUS_'.strtoupper($status);
        if(!defined($status_name)) return false;

        $update_data = array(
            'req_status'=>constant($status_name),
            'id'=>$id,
            'req_time' => time(),
        );
        if ($requestSuccess) {
            $update_data['status'] = self::STATUS_SUCCESS;
        }
        $this->setRow($update_data);

        return $this->update($update_data);
    }

    /**
     * 批量更新订单数据
     */
    public function setBatchReqStatus($ids, $status, $requestSuccess)
    {
        $status_name = 'self::REQ_STATUS_'.strtoupper($status);
        if(!defined($status_name)) return false;

        $update_data = array(
            'req_status'=>constant($status_name),
            'req_time' => time(),
        );

        if ($requestSuccess) {
            $update_data['status'] = self::STATUS_SUCCESS;
        }

        foreach ($ids as $id) {
            $update_data['id'] = $id;
            $this->setRow($update_data);
            $this->update($update_data);
        }
    }

    /**
     * 延时重试
     */
    public function delayRequest($logId) {
        $data = $this->find($logId);
        if (empty($data)) {
            return false;
        }
        // 超过10次停止重试
        if ($data['req_times'] > 10) {
            return false;
        }
        // 发送间隔频率
        $seq = array(30, 30, 30, 120, 360, 600, 7200, 10800, 21600, 36000, 36000, 36000);
        $data['req_times'] = $data['req_times'] + 1;
        //$data['req_status'] = 0;
        $data['req_time'] = get_gmtime();
        $data['next_req_time'] = get_gmtime() + $seq[$data['req_times']];
        $saveData = $data->getRow();
        $this->setRow($saveData);
        $this->update($saveData);
    }

    /**
     * 处理请求
     */
    public function processRequest($data, $isBatch = false)
    {
        //发送请求
        $params = $data['content'];
        
        // 自动识别转账模式
        $mode = app_conf('PAYMENT_TRANSFER_MODE');
        $mode = !empty($mode) ? $mode : 'transfer';
        if ($mode == 'mixed') {
            if (stripos($data['content']['orders'], 'COUPONLOG') > 0) {
                $data['type'] = 'pretransfer';
            }
            else {
                $data['type'] = 'transfer';
            }
        }
        else {
            $data['type'] = $mode;
        }
        $ret = PaymentApi::instance()->request($data['type'], $params);

        $requestSuccess = false;
        //Timeout异常处理
        // 支付未返回以及返回同步落单失败
        if (empty($ret) || ($data['type'] == 'pretransfer' && $ret['respCode'] == '01'))
        {
            if (!$isBatch)
            {
                $this->delayRequest($data['id']);
            }
            else
            {
                if (is_array($data['ids']))
                {
                    foreach ($data['ids'] as $id) {
                        $this->delayRequest($id);
                    }
                }
            }
            return;
        }
        else {
            $requestSuccess = true;
        }


        //设置队列处理状态为失败 status
        $result = $ret['respCode'] !== '00' ? false : true;
        if ($result === false)
        {
            // 如果交易转账返回失败，理财记录日志状态
            //$this->setQueueStatus($data['id'], FinanceQueueModel::STATUS_ERROR);
            PaymentApi::log("PaymentapiWorker error. type:{$data['type']}, ret:".json_encode($ret), Logger::ERR);
            return ;
        }
        //设置队列请求状态 req_status
        if ($isBatch)
        {
            $this->setBatchReqStatus($data['ids'], 'success', $requestSuccess);
        }
        else
        {
            $this->setReqStatus($data['id'], 'success', $requestSuccess);
        }

        // 兼容旧版本转账数据,旧接口需要同步修改转账细节数据
        //处理转账结果 设置detail_log表
        if ($data['type'] === 'transfer')
        {
            $this->processTransfer($ret);
        }

        return;
    }

    /**
     * 处理转账结果并返回处理结果
     */
    public function processTransferWithResponseMessage($result) {
        $batchId = isset($result['preBatchId']) ? trim($result['preBatchId']) : '';
        if (empty($batchId)) {
            return false;
        }
        $ret = array();
        $ret['respCode'] = 'S';
        $ret['respMsg'] = '';
        $sql = "UPDATE firstp2p_finance_detail_log SET status = 1 WHERE preBatchId = '{$batchId}' AND status = 0";
        $GLOBALS['db']->query($sql);
        $affected_rows = $GLOBALS['db']->affected_rows();
        if($affected_rows <= 0) {
            \libs\utils\Alarm::push('payment', '转账队列处理失败', json_encode($result));
            $ret['respCode'] = false;
            $ret['respMsg'] = '转账队列处理失败';
            return $ret;
        }
        return $ret;
    }

    /**
     * 处理转账结果
     */
    public function processTransfer($result)
    {
        $db = $GLOBALS['db'];

        $successIds = array();
        $failedIds = array();
        $failedTrans = array();

        //处理失败的订单
        foreach ($result['orders'] as $item)
        {
            $reason = addslashes($item['respMsg']);
            $orderId = addslashes($item['outOrderId']);

            if ($item['respCode'] == '00' || $item['respCode'] == self::REQ_STATUS_PROCESSED)
            {
                $successIds[] = $orderId;
                continue;
            }

            $failedIds[] = $orderId;
            $failedTrans[] = $item;

            $sql = "UPDATE ".DB_PREFIX."finance_detail_log SET status='2', reason='$reason' WHERE outOrderId='$orderId' LIMIT 1";
            $ret = $db->query($sql);
            PaymentApi::log("PaymentapiWorker transfer failed. code:{$item['respCode']}, sql:$sql, rows:".$db->affected_rows());
        }

        //处理成功的订单
        if (!empty($successIds))
        {
            $orderIdString = implode("','", $successIds);
            $sql = "UPDATE ".DB_PREFIX."finance_detail_log SET status='1' WHERE outOrderId IN ('$orderIdString')";
            $ret = $db->query($sql);
            PaymentApi::log("PaymentapiWorker transfer success. sql:$sql, rows:".$db->affected_rows());
        }

        //失败订单告警
        if (!empty($failedTrans))
        {
            \libs\utils\Alarm::push('payment', '转账队列有失败订单', $failedTrans);
        }
    }

}
