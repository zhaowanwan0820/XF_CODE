<?php
/**
 * p2p 交互的幂等表
 */

namespace core\service;
use core\service\P2pDepositoryService;
use core\dao\SupervisionIdempotentModel;

class P2pIdempotentService extends BaseService {

    // 回调状态
    const STATUS_WAIT = 0;      // 待处理
    const STATUS_SEND = 1;      // 已通知
    const STATUS_CALLBACK = 2;  // 已回调
    const STATUS_INVALID = 3;   // 无效订单

    // 处理状态
    const RESULT_WAIT = 0; // 待处理
    const RESULT_SUCC = 1; // 处理成功
    const RESULT_FAIL = 2; // 处理失败

    const TYPE_DEAL = 1; // 投资
    const TYPE_DEAL_LOAN = 2; // 放款
    const TYPE_DEAL_REPAY = 3; // 还款
    const TYPE_DEAL_CANCEL = 4; //流标

    /**
     * 获取order信息
     * @param $orderId
     * @return array
     */
    public static function getInfoByOrderId($orderId) {
        $res =SupervisionIdempotentModel::instance()->findBy("order_id='{$orderId}'");
        $orderInfo =  $res ? $res->_row : array();
        return $orderInfo;
    }

    /**
     * 根据标的ID获取标的放款时候的订单号
     * @param $dealId
     * @return bool
     */
    public static function getGrantOrderByDealId($dealId){
        $tableName = SupervisionIdempotentModel::instance()->tableName();
        $sql = "SELECT order_id FROM `".$tableName."` where deal_id={$dealId} AND `type`=".self::TYPE_DEAL_LOAN ." AND `status` !=".self::STATUS_INVALID;
        $res = SupervisionIdempotentModel::instance()->findBySql($sql, array(),true);
        return isset($res['order_id']) ? $res['order_id'] : false;
    }

    /**
     * 根据标的ID获取有效的放款订单信息
     * 每个标的放款信息只能有一条 这个由程序来控制
     * @param $dealId
     * @return mixed
     */
    public static function getValidGrantOrderInfoByDealId($dealId){
        $tableName = SupervisionIdempotentModel::instance()->tableName();
        $sql = "SELECT * FROM `".$tableName."` where deal_id={$dealId} AND `type`=".self::TYPE_DEAL_LOAN ." AND `status` !=".self::STATUS_INVALID;
        $res = SupervisionIdempotentModel::instance()->findBySql($sql, array(),true);
        return !empty($res) ? $res->getRow() : array();
    }

    public static function getOrderCntByTypeAndResult($type,$status){
        $tableName = SupervisionIdempotentModel::instance()->tableName();
        $sql = "SELECT count(*) as cnt FROM `".$tableName."` where `type`=".$type." AND `result` =".$status;
        return SupervisionIdempotentModel::instance()->countBySql($sql);
    }

    /**
     * 获取最后一次代扣订单信息
     * @param $dealId
     * @param $repayId
     */
    public static function getDkOrderInfoByDealIdAndRepayId($dealId,$repayId){
        $tableName = SupervisionIdempotentModel::instance()->tableName();
        $sql = "SELECT * FROM `".$tableName."` where deal_id={$dealId} AND `type`=".P2pDepositoryService::IDEMPOTENT_TYPE_DK ." AND repay_id ={$repayId} order by id DESC limit 1";
        $res = SupervisionIdempotentModel::instance()->findBySql($sql, array(),true);
        return !empty($res) ? $res->getRow() : array();
    }

    /**
     * 获取最后一次利息划转信息
     * @param $dealId
     * @param $repayId
     * @return array
     */
    public static function getTransOrderInfoByDealIdAndRepayId($dealId,$repayId){
        $tableName = SupervisionIdempotentModel::instance()->tableName();
        $sql = "SELECT * FROM `".$tableName."` where deal_id={$dealId} AND `type`=".P2pDepositoryService::IDEMPOTENT_TYPE_TRANS ." AND repay_id ={$repayId} order by id DESC limit 1";
        $res = SupervisionIdempotentModel::instance()->findBySql($sql, array(),true);
        return !empty($res) ? $res->getRow() : array();
    }

    public static function getDkOrderCntDealIdAndRepayId($dealId,$repayId){
        $tableName = SupervisionIdempotentModel::instance()->tableName();
        $sql = "SELECT count(*) as cnt FROM `".$tableName."` where deal_id={$dealId} AND `type`=".P2pDepositoryService::IDEMPOTENT_TYPE_DK ." AND repay_id ={$repayId}";
        return SupervisionIdempotentModel::instance()->countBySql($sql);
    }

    /**
     * 标的放款只有一次，重复操作要将以前订单置为无效
     * @param $dealId
     * @return mixed
     */
    public static function invalidGrantOrderByDealId($dealId){
        $tableName = SupervisionIdempotentModel::instance()->tableName();
        $sql = "UPDATE {$tableName} SET `status` = ".self::STATUS_INVALID ." WHERE deal_id=".$dealId." AND `type` = ".self::TYPE_DEAL_LOAN;
        return SupervisionIdempotentModel::instance()->execute($sql);
    }

    /**
     * 根据标的ID获取标的流标时候的订单号
     * @param $dealId
     * @return bool
     */
    public static function getCancelOrderByDealId($dealId){
        $tableName = SupervisionIdempotentModel::instance()->tableName();
        $sql = "SELECT order_id FROM `".$tableName."` where deal_id={$dealId} AND type=".self::TYPE_DEAL_CANCEL;
        $res = SupervisionIdempotentModel::instance()->findBySql($sql, array(),true);
        return isset($res['order_id']) ? $res['order_id'] : false;
    }

    /**
     * 更改订单信息
     * @param $orderId
     * @param $data
     * @return mixed
     */
    public static function updateOrderInfo($orderId,$data,$updateCondition=""){
        $condition = "order_id='{$orderId}'";
        if(!empty($updateCondition)){
            $condition.=" AND " . $updateCondition;
        }
        $data['update_time'] = isset($data['update_time']) ? $data['update_time'] : time();
        return SupervisionIdempotentModel::instance()->updateBy($data,$condition);
    }


    /**
     * 新增订单信息
     * @param $orderId
     * @param $data
     * @return bool
     */
    public static function addOrderInfo($orderId,$data) {
        $now  = time();
        try{
            $m = new SupervisionIdempotentModel();
            $m->order_id = $orderId;
            foreach($data as $k=>$v) {
                $m->$k = $v;
            }
            $m->create_time = $now;
            $m->update_time = $now;
            $res = $m->save();
        }catch (\Exception $ex) {
            \libs\utils\Logger::error($ex->getMessage());
            return false;
        }
        return true;
    }


    public static function saveOrderInfo($orderId,$data,$updateCondition="",$isBid=false){
        $order = SupervisionIdempotentModel::instance()->findBy("order_id='$orderId'");
        if(!$order){
            $data['params'] = $isBid ? addslashes($data['params']) : $data['params'];
            return self::addOrderInfo($orderId,$data);
        }else{
            return self::updateOrderInfo($orderId,$data,$updateCondition);
        }
    }


    public static function updateOrderInfoByResult($orderId,$data,$result){
        $data['update_time'] = isset($data['update_time']) ? $data['update_time'] : time();
        $condition = "order_id='$orderId' AND result={$result}";
        return SupervisionIdempotentModel::instance()->updateAll($data,$condition, true);
    }

    /**
     * 修改token状态
     * @param $token
     * @param $status
     * @return bool
     */
    public static function updateStatusByOrderId($orderId,$status) {
        $status = intval($status);
        $res = SupervisionIdempotentModel::instance()->updateBy(array('status'=>$status),"order_id='".$orderId."'");
        return $res;
    }

    /**
     * 根据订单ID获取投资ID
     * @param $orderId
     * @return mixed
     */
    public static function getLoadIdByOrderId($orderId){
        $orderInfo = self::getInfoByOrderId($orderId);
        if(!$orderInfo){
            return false;
        }
        return isset($orderInfo['load_id']) ? $orderInfo['load_id'] : false;
    }
}
