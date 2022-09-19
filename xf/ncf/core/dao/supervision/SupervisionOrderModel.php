<?php
/**
 * 存管订单表
 * SupervisionOrderModel class file.
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/

namespace core\dao\supervision;

use libs\db\Db;
use core\dao\BaseModel;

/**
 * SupervisionOrderModel class
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/
class SupervisionOrderModel extends BaseModel {

    //订单状态
    const ORDER_STATUS_WAITING = 0; //等待处理
    const ORDER_STATUS_SUCCESS = 1; //成功
    const ORDER_STATUS_FAILURE = 2; //失败
    const ORDER_STATUS_PROCESSING = 3; //处理中
    const ORDER_STATUS_CANCEL = 4; //撤销

    //业务类型
    const BIZ_TYPE_RECHARGE = 1; //充值到存管账户
    const BIZ_TYPE_WITHDRAW = 2; //存管账户提现到银行卡
    const BIZ_TYPE_SUPER_WITHDRAW = 3; //存管账户划转至超级账户
    const BIZ_TYPE_SUPER_RECHARGE = 4; //超级账户划转至存管账户
    const BIZ_TYPE_ELEC_WITHDRAW = 5; //提现到银信通账户
    const BIZ_TYPE_ENTRUSTED_WITHDRAW = 6; //受托提现
    const BIZ_TYPE_INVEST_CREATE = 7; //投资
    const BIZ_TYPE_RED_PACKETS = 8; //投资红包
    const BIZ_TYPE_DEAL_GRANT = 9; //放款
    const BIZ_TYPE_SHARE_PROFIT= 10; //放款分润
    const BIZ_TYPE_DEAL_REPAY = 11; //还款
    const BIZ_TYPE_DEAL_REPLACE_REPAY = 12; //代偿
    const BIZ_TYPE_DEAL_RETURN_REPAY = 13; //还代偿
    const BIZ_TYPE_GAINFEES = 14; //收费
    const BIZ_TYPE_DT_BID = 15; // 智多鑫投资 
    const BIZ_TYPE_DT_CREDITASSIGN = 16; // 智多鑫债转
    const BIZ_TYPE_DT_REDEEM = 17; // 智多鑫赎回
    const BIZ_TYPE_AUTORECHARGE = 18; // 存管自动扣款充值
    const BIZ_TYPE_DEAL_REPLACE_RECHARGE_REPAY = 19; //还充值还款
    const BIZ_TYPE_BATCH_TRANSFER = 20; //批量转账
    const BIZ_CREDIT_LOAN_WITHDRAW = 21; //速贷提现
    const BIZ_TYPE_DEAL_REPAY_FEE = 22; //还款手续费

    //对账状态
    const CHECK_STATUS_NORMAL = 0; //未处理
    const CHECK_STATUS_SUCCESS = 1; //已对账

    /**
     * 连firstp2p_payment库
     */
    public function __construct()
    {
        $this->db = Db::getInstance('firstp2p_payment');
        parent::__construct();
    }


    /**
     * 添加订单
     * @param string $outOrderId 外部订单号
     * @param int $bizType 业务类型
     * @param int $amount 单位分
     * @param int $dealId 标的id
     * @param int $parentId 父id
     * @param int $orderStatus 订单状态
     * @param int $userId 用户id
     * @return boolean
     */
    public function addOrder($outOrderId, $bizType, $amount, $dealId = 0, $parentId = 0, $orderStatus = 0, $userId = 0) {
        $data = array(
            'parent_id'     => (int) $parentId,
            'out_order_id'  => addslashes($outOrderId),
            'biz_type'      => (int) $bizType,
            'amount'        => (int) $amount,
            'deal_id'       => (int) $dealId,
            'order_status'  => (int) $orderStatus,
            'user_id'        => (int) $userId,
            'create_time'   => time(),
            'update_time'   => time(),
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 更新订单数据
     */
    public function updateOrderData($outOrderId, $bizType, $amount, $dealId = 0, $parentId = 0, $orderStatus = 0, $userId = 0) {
        if (empty($outOrderId)) {
            return false;
        }
        $condition = sprintf("`out_order_id` = '%s'", addslashes($outOrderId));
        $params = array(
            'parent_id'     => (int) $parentId,
            'biz_type'      => (int) $bizType,
            'amount'        => (int) $amount,
            'deal_id'       => (int) $dealId,
            'order_status'  => (int) $orderStatus,
            'user_id'       => (int) $userId,
            'update_time'   => time(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 更新订单
     * @param string $outOrderId 外部订单号
     * @param int $orderStatus 订单状态
     * @return boolean
     */
    public function updateOrder($outOrderId, $orderStatus) {
        if (empty($outOrderId)) {
            return false;
        }
        $condition = sprintf("`out_order_id` = '%s' and `order_status` not in (%s)", addslashes($outOrderId), self::ORDER_STATUS_SUCCESS . ',' . self::ORDER_STATUS_FAILURE);
        //成功状态才可以修改为撤销
        if ($orderStatus == self::ORDER_STATUS_CANCEL) {
            $condition = sprintf("`out_order_id` = '%s' and `order_status` = '%d'", addslashes($outOrderId), self::ORDER_STATUS_SUCCESS);
        }
        $params = array(
            'order_status'  => $orderStatus,
            'update_time'   => time(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 通过父单号更新订单
     * @param int $parentId 父单号
     * @param int $orderStatus 订单状态
     * @return boolean
     */
    public function updateOrderByPid($parentId, $orderStatus) {
        if (empty($parentId)) {
            return false;
        }
        $condition = sprintf("`parent_id` = '%d' and `order_status` not in (%s)", intval($parentId), self::ORDER_STATUS_SUCCESS . ',' . self::ORDER_STATUS_FAILURE);
        //成功状态才可以修改为撤销
        if ($orderStatus == self::ORDER_STATUS_CANCEL) {
            $condition = sprintf("`parent_id` = '%d' and `order_status` = '%d'", intval($parentId), self::ORDER_STATUS_SUCCESS);
        }
        $params = array(
            'order_status'  => $orderStatus,
            'update_time'   => time(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 按标更新订单
     * @param int $outOrderId 外部订单号
     * @param int $orderStatus 订单状态
     * @return boolean
     */
    public function updateOrderByDealId($dealId, $orderStatus) {
        if (empty($dealId)) {
            return false;
        }
        $condition = sprintf("`deal_id` = '%d' and `order_status` not in (%s)", intval($dealId), self::ORDER_STATUS_SUCCESS . ',' . self::ORDER_STATUS_FAILURE);
        //成功状态才可以修改为撤销
        if ($orderStatus == self::ORDER_STATUS_CANCEL) {
            $condition = sprintf("`deal_id` = '%d' and `order_status` = '%d'", intval($dealId), self::ORDER_STATUS_SUCCESS);
        }
        $params = array(
            'order_status'  => $orderStatus,
            'update_time'   => time(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 通过外部订单号查询
     * @param string $outOrderId 外部订单号
     * @return mix
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
     * 通过父单号查询
     * @param int $parentId 父单号
     * @return mix
     */
    public function getInfoByPid($parentId) {
        $condition = sprintf("`parent_id` = '%d'", intval($parentId));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 通过标的id查询
     * @param int $dealId 标的id
     * @return mix
     */
    public function getListByDealId($dealId) {
        $condition = sprintf("`deal_id` = '%d'", intval($dealId));
        $ret = $this->findAll($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 订单对账
     */
    public function orderCheck($outOrderId, $check_date) {
        $condition = sprintf("`out_order_id` = '%s'", addslashes($outOrderId));
        $params = array(
            'check_date'    => $check_date,
            'check_status'  => self::CHECK_STATUS_SUCCESS,
            'check_time'    => time(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 通过日期获取记录
     * @return mix
     */
    public function getListByDate($date, $offset = 0, $pageSize = 20) {
        $orderBy = ' ORDER BY `id` ASC ';
        $limit = sprintf(' LIMIT %d, %d ', $offset, $pageSize);
        //成功 撤销
        //提现失败、受托提现失败、存管划转到理财失败
        $where = sprintf(" `update_time` >= '%d' AND `update_time` < '%d' AND (`order_status` IN (%s) OR (`biz_type` in (%s) AND `order_status` = '%d')) ", strtotime($date), strtotime($date) + 24*60*60, 
            implode(', ', [self::ORDER_STATUS_SUCCESS, self::ORDER_STATUS_CANCEL]), implode(', ', [self::BIZ_TYPE_WITHDRAW, self::BIZ_TYPE_ENTRUSTED_WITHDRAW, self::BIZ_TYPE_SUPER_WITHDRAW]), self::ORDER_STATUS_FAILURE);
        $ret = $this->findAll($where . $orderBy . $limit);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }
}
