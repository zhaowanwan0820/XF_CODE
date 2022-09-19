<?php
/**
 * p2p 交互的幂等表
 */

namespace core\service;
use core\dao\ZxIdempotentModel;

class ZxIdempotentService extends BaseService {

    // 订单状态
    const STATUS_WAIT = 0;      // 待处理
    const STATUS_SEND = 1;      // 已通知
    const STATUS_CALLBACK  = 2;  // 已回调
    const STATUS_INVALID = 3;  // 作废

    // 处理状态
    const RESULT_WAIT = 0;  // 待处理
    const RESULT_SUCC = 1;  // 处理成功
    const RESULT_FAIL = 2;  // 处理失败

    const REPAY_TYPE_EARLY = 1; // 提前还款
    const REPAY_TYPE_NORMAL = 2; // 正常还款

    const MONEY_TYPE_PRINCIPAL = 1;
    const MONEY_TYPE_INTEREST = 2;
    const MONEY_TYPE_CONSULTFEE = 3;
    const MONEY_TYPE_LOANFEE = 4;
    const MONEY_TYPE_GUARANTEEFEE = 5;
    const MONEY_TYPE_PAYFEE = 6;
    const MONEY_TYPE_CANALFEE = 7;
    const MONEY_TYPE_MANAGEMENTFEE = 8;
    const MONEY_TYPE_YXT_PRINCIPAL = 9; // 银信通本金
    const MONEY_TYPE_YXT_INTEREST = 10; // 银信通利息
    const MONEY_TYPE_YXT_FEE = 11; //银信通服务费
    const MONEY_TYPE_YXT_RETURN = 12;// 银信通返还借款人金额

    const BIZ_TYPE_REPAY  = 1;   // 正常还款业务
    const BIZ_TYPE_YXT_TJ = 2;     // 银信通统计业务
    const BIZ_TYPE_YXT_DF = 3;     // 银信通代发业务
    const BIZ_TYPE_SUDAI  = 4;   // 速贷还款业务

    const NEED_TRANS_YES = 1; // 需要代发
    const NEED_TRANS_NO = 2; // 不需要代发


    public static function getInfoByBatchOrderId($batchOrderId){
        $res =ZxIdempotentModel::instance()->findAll("batch_order_id='{$batchOrderId}'");
        $orderInfo =  $res ? $res->_row : array();
        return $orderInfo;
    }





    public static function getSumMoneyByBizType($batchOrderId){
       return ZxIdempotentModel::instance()->getSumMoneyByBizType($batchOrderId);
    }



    /**
     * 获取order信息
     * @param $orderId
     * @return array
     */
    public static function getInfoByOrderId($orderId) {
        $res =ZxIdempotentModel::instance()->findBy("order_id='{$orderId}'");
        $orderInfo =  $res ? $res->_row : array();
        return $orderInfo;
    }



    public static function addOrderInfo($orderId,$data){
        $now  = time();
        try{
            $m = new ZxIdempotentModel();
            $m->order_id = $orderId;
            foreach($data as $k=>$v) {
                $m->$k = $v;
            }
            $m->create_time = $now;
            $m->update_time = $now;
            $res = $m->save();
            return $res ? $m->id : false;
        }catch (\Exception $ex) {
            \libs\utils\Logger::error($ex->getMessage());
            return false;
        }
    }

    public static function updateOrderInfo($orderId,$data,$updateCondition=""){
        $condition = "order_id='{$orderId}'";
        if(!empty($updateCondition)){
            $condition.=" AND " . $updateCondition;
        }
        $data['update_time'] = isset($data['update_time']) ? $data['update_time'] : time();
        return ZxIdempotentModel::instance()->updateAll($data,$condition,true);
    }

    public static function updateOrderInfoByResult($orderId,$data,$result){
        $data['update_time'] = isset($data['update_time']) ? $data['update_time'] : time();
        $condition = "order_id='$orderId' AND result={$result}";
        return ZxIdempotentModel::instance()->updateBy($data,$condition, true);
    }

    // ###############################################################################

    /**
     * 取得正常还款未代发记录
     * @param $batchOrderId
     * @return array
     */
    public static function getRepayTransByBatchOrderId($batchOrderId){
        $cond = "batch_order_id='{$batchOrderId}' AND biz_type=".self::BIZ_TYPE_REPAY." and status=".self::STATUS_WAIT." AND is_need_trans=".self::NEED_TRANS_YES;
        $res =ZxIdempotentModel::instance()->findAll($cond,true);
        return $res;
    }

    /**
     * 取得银信通还款未代发记录
     * @param $batchOrderId
     * @return array
     */
    public static function getYXTTransByBatchOrderId($batchOrderId){
        $cond = "batch_order_id='{$batchOrderId}' AND biz_type=".self::BIZ_TYPE_YXT_DF." and status=".self::STATUS_WAIT." AND is_need_trans=".self::NEED_TRANS_YES;
        return ZxIdempotentModel::instance()->findAll($cond,true);
    }

    /**
     * 取得银信通统计记录
     * @param $batchOrderId
     * @return array
     */
    public static function getYXTTransRecordByBatchOrderId($batchOrderId){
        $cond = "batch_order_id='{$batchOrderId}' AND biz_type=".self::BIZ_TYPE_YXT_TJ." and status=".self::STATUS_WAIT." AND is_need_trans=".self::NEED_TRANS_NO;
        return ZxIdempotentModel::instance()->findAll($cond,true);
    }

    public static function getYXTParentOrder($userId,$dealId){
        $cond = "receiver_uid=".$userId." AND deal_id=".$dealId." AND biz_type=".self::BIZ_TYPE_YXT_TJ." and result=".self::RESULT_WAIT." AND is_need_trans=".self::NEED_TRANS_NO;
        return ZxIdempotentModel::instance()->findBy($cond,'*');
    }

    //获取代发未成功处理的数量
    public static function getTransUnSuccCnt($batchOrderId){
        $unSuccResultStr = self::RESULT_WAIT . "," . self::RESULT_FAIL;
        $bizTypes = self::BIZ_TYPE_YXT_TJ . "," . self::BIZ_TYPE_YXT_DF . "," .self::BIZ_TYPE_REPAY;
        return ZxIdempotentModel::instance()->count("batch_order_id={$batchOrderId} AND `result` IN ({$unSuccResultStr}) AND biz_type in ($bizTypes)");
    }

    /**
     * 通过批次ID获取项目ID
     * @param $batchOrderId
     * @return int|mixed
     */
    public static function getProjectIdByBatchOrderId($batchOrderId){
        $res =ZxIdempotentModel::instance()->findBy("batch_order_id='{$batchOrderId}'");
        $orderInfo =  $res ? $res->getRow() : array();
        return !empty($orderInfo['project_id']) ? $orderInfo['project_id'] : 0;
    }
}
