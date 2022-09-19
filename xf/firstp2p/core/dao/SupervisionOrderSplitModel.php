<?php
/**
 * 存管订单批量拆分表
 *
 * 针对P2P项目标的，单标的放还款涉及的转账笔数超过2000时，理财系统底层需将放还款做批次拆分，
 * 以保证单批次提交至海口行存管系统的转账笔数小于等于2000。
 **/

namespace core\dao;
use libs\utils\Logger;

class SupervisionOrderSplitModel extends BaseModel {
    // 订单状态
    const ORDER_STATUS_WAITING = 0; //已受理
    const ORDER_STATUS_SUCCESS = 1; //成功
    const ORDER_STATUS_FAILURE = 2; //失败
    const ORDER_STATUS_PROCESSING = 3; //处理中

    // 业务类型
    const BIZ_TYPE_DEAL_REPAY = 1; //还款
    const BIZ_TYPE_DEAL_REPLACE_REPAY = 2; //代偿还款
    const BIZ_TYPE_DEAL_GRANT = 3; //放款
    const BIZ_TYPE_DEAL_REPLACE_RECHARGE_REPAY = 4; //代充值还款

    // 订单拆分的主订单缓存key
    const ORDER_SPLIT_KEY = 'ORDERSPLIT_ORDERID_%s';

    /**
     * 连接payment库
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 通过外部订单号查询
     * @param string $outOrderId 外部订单号
     * @return array
     */
    public function getInfoByOutOrderId($outOrderId) {
        $condition = sprintf("`out_order_id` = '%s'", addslashes($outOrderId));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 通过业务订单号查询
     * @param int $orderId 业务订单号
     * @return array
     */
    public function getInfoByOrderId($orderId) {
        $condition = sprintf("`order_id` = '%d'", intval($orderId));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 通过业务订单号查询所有外部交易流水号数据
     * @param int $orderId 业务订单号
     * @return array
     */
    public function getAllByOrderId($orderId) {
        $condition = sprintf("`order_id` = '%d'", intval($orderId));
        $ret = $this->findAll($condition, true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 新增存管订单拆分数据
     * @param string $outOrderId 外部订单号
     * @param string $orderId 业务订单号
     * @param int $bizType 业务类型
     * @param int $payUserId 还款人用户id
     * @param int $dealId 标的id
     * @param int $status 订单状态
     * @param int $totalNum 该批次总条数
     * @param int $totalAmount 该批次总金额,单位分
     * @return boolean
     */
    public function addOrderSplit($outOrderId, $orderId, $bizType, $payUserId, $dealId, $orderStatus, $totalNum, $totalAmount) {
        try{
            $data = array(
                'out_order_id'  => addslashes($outOrderId),
                'order_id'      => addslashes($orderId),
                'biz_type'      => (int)$bizType,
                'pay_user_id'   => (int)$payUserId,
                'deal_id'       => (int)$dealId,
                'order_status'  => (int)$orderStatus,
                'total_num'     => (int)$totalNum,
                'total_amount'  => (int)$totalAmount,
                'create_time'   => time(),
            );
            $this->setRow($data);
            $ret = $this->insert();
            if ($ret) {
                return $this->db->insert_id();
            }
            return false;
        }catch (\Exception $e) {
            Logger::error('SupervisionOrderSplitModel::addOrder, errMsg:' . $e->getMessage());
            return false;
        }
    }

    /**
     * 更新存管订单拆分数据
     * @param string $outOrderId 外部订单号
     * @param array $data 需要更新的数据
     * @return boolean
     */
    public function updateOrderSplitData($outOrderId, $data) {
        if (empty($outOrderId) || empty($data)) {
            return false;
        }
        $condition = sprintf("`out_order_id` = '%s'", addslashes($outOrderId));
        !empty($data['orderId']) && $params['order_id'] = addslashes($data['orderId']);
        !empty($data['bizType']) && $params['biz_type'] = (int)$data['bizType'];
        !empty($data['payUserId']) && $params['pay_user_id'] = (int)$data['payUserId'];
        !empty($data['dealId']) && $params['deal_id'] = (int)$data['dealId'];
        isset($data['orderStatus']) && $params['order_status'] = (int)$data['orderStatus'];
        !empty($data['totalNum']) && $params['total_num'] = (int)$data['totalNum'];
        !empty($data['totalAmount']) && $params['total_amount'] = (int)$data['totalAmount'];
        !empty($data['jobsId']) && $params['jobs_id'] = (int)$data['jobsId'];
        !empty($data['memo']) && $params['memo'] = addslashes($data['memo']);
        $params['update_time'] = time();

        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 更新订单状态
     * @param string $outOrderId 外部订单号
     * @param int $orderStatus 订单状态
     * @return boolean
     */
    public function updateOrderSplitStatus($outOrderId, $orderStatus, $memo = '') {
        if (empty($outOrderId)) {
            return false;
        }
        $condition = sprintf("`out_order_id` = '%s' AND `order_status` NOT IN (%s)", addslashes($outOrderId), self::ORDER_STATUS_SUCCESS . ',' . self::ORDER_STATUS_FAILURE);
        $params = array(
            'order_status'  => $orderStatus,
            'memo' => addslashes($memo),
            'update_time'   => time(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 记录拆分后的订单号
     * @param int $orderId 业务订单号
     * @param int $outOrderId 外部订单号
     */
    public function addOrderIdCache($orderId, $outOrderId, $expireTime = 86400) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }
        $cacheKey = sprintf(self::ORDER_SPLIT_KEY, $orderId);
        $redis->SADD($cacheKey, $outOrderId);
        $redis->EXPIRE($cacheKey, (int)$expireTime);
        return true;
    }

    /**
     * 检查业务订单号、外部订单号是否存在
     * @param int $orderId 业务订单号
     * @param int $outOrderId 外部订单号
     * @param boolean $fromDb 是否读取数据表
     */
    public function isExistOrderIdCache($orderId, $outOrderId, $fromDb = true) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }
        $cacheKey = sprintf(self::ORDER_SPLIT_KEY, $orderId);
        $isExist = $redis->SISMEMBER($cacheKey, $outOrderId);
        if ($isExist || !$fromDb) {
            return $isExist;
        }

        // 通过业务订单号查询所有外部交易流水号数据
        $list = $this->getAllByOrderId($orderId);
        if (empty($list)) {
            return false;
        }

        $updateOrderIdRet = false;
        foreach ($list as $item) {
            if (empty($item) || in_array($item['order_status'], [self::ORDER_STATUS_SUCCESS, self::ORDER_STATUS_FAILURE])) {
                continue;
            }
            $this->addOrderIdCache($item['order_id'], $item['out_order_id']);
            $updateOrderIdRet = true;
        }
        return $updateOrderIdRet;
    }

    /**
     * 踢出已经处理过的外部订单号
     * @param int $orderId 业务订单号
     * @param int $outOrderId 外部订单号
     */
    public function sRemOrderIdCache($orderId, $outOrderId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }
        $cacheKey = sprintf(self::ORDER_SPLIT_KEY, $orderId);
        return $redis->SREM($cacheKey, $outOrderId);
    }

    /**
     * 获取缓存中的外部订单号列表
     * @param int $orderId 业务订单号
     */
    public function sMembersOrderIdCache($orderId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }
        $cacheKey = sprintf(self::ORDER_SPLIT_KEY, $orderId);
        return $redis->SMEMBERS($cacheKey);
    }

    /**
     * 根据外部交易流水号，存管订单补redis数据
     * @param int $outOrderId
     * @return multitype:boolean multitype:
     */
    public function orderSplitRetryRedis($outOrderId) {
        $data = $this->getInfoByOutOrderId($outOrderId);
        if (empty($data)) {
            return ['ret'=>false, 'errMsg'=>'该外部交易流水号不存在', 'data'=>''];
        }
        if (in_array($data['order_status'], [self::ORDER_STATUS_SUCCESS, self::ORDER_STATUS_FAILURE])) {
            return ['ret'=>false, 'errMsg'=>'该外部交易流水号已终态', 'data'=>''];
        }

        // 通过业务订单号查询所有外部交易流水号数据
        $list = $this->getAllByOrderId($data['order_id']);
        if (empty($list)) {
            return ['ret'=>false, 'errMsg'=>'该业务流水号不存在', 'data'=>'orderId:' . $data['order_id']];
        }

        $updateData = [];
        foreach ($list as $item) {
            if (empty($item) || in_array($item['order_status'], [self::ORDER_STATUS_SUCCESS, self::ORDER_STATUS_FAILURE])) {
                continue;
            }

            // 检查业务订单号、外部订单号是否存在
            $isExist = $this->isExistOrderIdCache($item['order_id'], $item['out_order_id'], false);
            if (true === $isExist) {
                continue;
            }
            $this->addOrderIdCache($item['order_id'], $item['out_order_id']);
            $updateData[] = $item['out_order_id'];
        }
        if (!empty($updateData)) {
            return ['ret'=>true, 'errMsg'=>'成功', 'data'=>$item['order_id'].'|'.join(',',$updateData)];
        }
        return ['ret'=>false, 'errMsg'=>'该业务流水号的订单都已终态或无需补数据', 'data'=>'orderId:' . $data['order_id']];
    }
}
