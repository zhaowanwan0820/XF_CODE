<?php
/**
 * PartialRepayModel.php
 * @date 2018-06-06
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */

namespace core\dao\repay;

use libs\utils\Logger;
use core\enum\PartialRepayEnum;
use core\dao\BaseModel;


/**
 * 部分还款服务类
 *
 * Class PartialRepayModel
 * @package core\dao
 */

class PartialRepayModel extends BaseModel {

    /**
     * 获得某个还款账户的还款总金额
     * @param $repayId 还款Id
     * @param $repayType 还款类型
     * @param array $feeTypes 费用类型
     * @return int
     */
    public function getRepayMoney($repayId,$repayType,$feeTypes = array()) {
        $feeTypeCond = '';
        if(!empty($feeTypes)) {
            if(!is_array($feeTypes)) {
                $feeTypes = array($feeTypes);
            }
            $feeTypeCond = ' AND type IN('.implode(',',$feeTypes).') ';
        }

        $sql = "SELECT sum(money) AS `sum` FROM %s WHERE `repay_id`='%d' AND repay_type = %d %s";
        $sql = sprintf($sql, 'firstp2p_partial_repay_detail', $repayId,$repayType,$feeTypeCond);
        $res = $this->findBySql($sql);
        return $res['sum'] > 0 ? $res['sum'] : 0;
    }

    public function getMoneyByLoanId($repayId,$loanId,$repayType,$feeTypes = array()) {
        $feeTypeCond = '';
        if(!empty($feeTypes)) {
            if(!is_array($feeTypes)) {
                $feeTypes = array($feeTypes);
            }
            $feeTypeCond = ' AND type IN('.implode(',',$feeTypes).') ';
        }

        $sql = "SELECT sum(money) AS `sum` FROM %s WHERE `repay_id`='%d' AND deal_loan_id = %d AND repay_type = %d %s";
        $sql = sprintf($sql, 'firstp2p_partial_repay_detail', $repayId, $loanId, $repayType,$feeTypeCond);
        $res = $this->findBySql($sql);
        return $res['sum'] > 0 ? $res['sum'] : 0;
    }


    public function getPrepayMoneyByLoanId($prepayId,$loanId,$repayType,$feeTypes = array()) {
        $feeTypeCond = '';
        if(!empty($feeTypes)) {
            if(!is_array($feeTypes)) {
                $feeTypes = array($feeTypes);
            }
            $feeTypeCond = ' AND type IN('.implode(',',$feeTypes).') ';
        }

        $sql = "SELECT sum(money) AS `sum` FROM %s WHERE `prepay_id`='%d' AND deal_loan_id = %d AND repay_type = %d %s";
        $sql = sprintf($sql, 'firstp2p_partial_repay_detail', $prepayId, $loanId, $repayType,$feeTypeCond);
        $res = $this->findBySql($sql);
        return $res['sum'] > 0 ? $res['sum'] : 0;
    }

    /**
     * 批量订单是否存在
     * @param $batchOrderId 批量订单Id
     * @return array
     */
    public function isBatchOrderIdExist($batchOrderId) {
        return array();
    }

    public function getInfoByOrderId($orderId) {
        return array();
    }

    /**
     * 获取部分还款订单列表
     * @param $orderId 还款批次订单号
     * @param $repayType 还款方式
     * @return array
     */
    public function getPartialRepayOrderList($batchOrderId,$repayType) {
        $sql = "SELECT * FROM firstp2p_partial_repay_detail WHERE batch_order_id= {$batchOrderId} AND repay_type = {$repayType} ";
        $result = $this->findAllBySql($sql,true);
        return $result;
    }
    /**
     * 保存部分还款订单
     * @param array $repayData
     * @param array $repayDetailList
     * @return boolean
     */
    public function savePartialRepayOrder($batchorderId,$repayData,$repayDetailList, $bizType = 0) {
        // 检查该交易流水号是否已存在
        if ($this->getInfoByOrderId($batchorderId)) {
            Logger::info(sprintf('%s | %s, 该业务的交易流水号已存在, orderId: %s', __CLASS__, __FUNCTION__, $batchorderId));
            return true;
        }
        $listCount = count($repayDetailList);
        $repayDetailListOld = $repayDetailList;

        $db =\libs\db\Db::getInstance('firstp2p');
        try{
            $db->startTrans();
            if ($bizType) {
                $insertSqlTpl = "INSERT INTO %s (order_id,batch_order_id,repay_id,prepay_id,pay_user_id,receive_user_id,deal_loan_id,money,type,deal_id,repay_type,status,note,create_time,update_time, biz_type) VALUES %s ";
                $valuesSql = "('%s','%s', %d, %d, %d, %d, %d, %f, %d, %d, %d, %d, '%s', %d, %d, $bizType)";
        } else {
                $insertSqlTpl = "INSERT INTO %s (order_id,batch_order_id,repay_id,prepay_id,pay_user_id,receive_user_id,deal_loan_id,money,type,deal_id,repay_type,status,note,create_time,update_time) VALUES %s ";
                $valuesSql = "('%s','%s', %d, %d, %d, %d, %d, %f, %d, %d, %d, %d, '%s', %d, %d)";
            }
            $totalInsertRows = 0;

            $now = time();
            while (count($repayDetailList) > 0) {
                $tmpList = array_splice($repayDetailList,0,PartialRepayEnum::ORDER_INSERT_SPLIT_NUM);
                $tmpCount = count($tmpList);
                $valuesArr = array();
                foreach ($tmpList as $tmpItem) {
                    $valuesArr[] = sprintf($valuesSql,
                        $tmpItem['orderId'],$batchorderId,$repayData['repayId'],$repayData['prepayId'],
                        $tmpItem['payUserId'], $tmpItem['receiveUserId'],$tmpItem['dealLoanId'] ,$tmpItem['amount'], $tmpItem['type'],
                        $repayData['dealId'],$tmpItem['repayType'],PartialRepayEnum::STATUS_NORMAL,'',  $now, $now);
                    $totalInsertRows += 1;
                }

                $insertSql = sprintf($insertSqlTpl, 'firstp2p_partial_repay_detail', implode(',',$valuesArr));
                $db->query($insertSql);
                $insertRows =$db->affected_rows();
                if($insertRows != $tmpCount) {//是否全部插入成功
                    throw new \Exception("插入数据不一致 tmpCount:" . $tmpCount . " insertRows:" . $insertRows);
                }
            }
            if($totalInsertRows != $listCount) {
                throw new \Exception("总数据不一致 listCount:" . $listCount . " totalInsertRows:" . $totalInsertRows);
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error(sprintf('%s | %s, 保存部分还款订单失败| 订单号:%s，异常内容:%s', __CLASS__, __FUNCTION__,  $batchorderId, $e->getMessage()));
            return false;
        }
        Logger::info(sprintf('%s | %s, 保存部分还款订单成功, orderId: %s', __CLASS__, __FUNCTION__, $batchorderId));
        return true;
    }

    public function getPrepayMoney($prepayId,$repayType,$feeTypes = array()) {
        $feeTypeCond = '';
        if(!empty($feeTypes)) {
            if(!is_array($feeTypes)) {
                $feeTypes = array($feeTypes);
            }
            $feeTypeCond = ' AND type IN('.implode(',',$feeTypes).') ';
        }

        $sql = "SELECT sum(money) AS `sum` FROM %s WHERE `prepay_id`='%d' AND repay_type = %d %s";
        $sql = sprintf($sql, 'firstp2p_partial_repay_detail', $prepayId,$repayType,$feeTypeCond);
        $res = $this->findBySql($sql);
        return $res['sum'] > 0 ? $res['sum'] : 0;
    }
}
