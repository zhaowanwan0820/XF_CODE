<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/8/4
 * Time: 15:41
 */

namespace core\dao;


class ThirdpartyOrderModel extends BaseModel
{

    const ORDER_ALREADY_EXISTED = -1;
    const ORDER_CREATED_SUCCESS = 1;
    const ORDER_CREATED_FAILED = 0;

    const ORDER_WAITING = 0;
    const ORDER_PROCESSING = 1;
    const ORDER_SUCCESS = 2;
    const ORDER_FAILED = 3;

    public function createOrderRecord($siteId, $orderId, $userId, $mobile, $dealId, $buyAmount, $dealLoanId, $bidTransferId)
    {
        $this->site_id = $siteId;
        $this->order_id = $orderId;
        $this->type = 0;
        $this->order_status = ThirdpartyOrderModel::ORDER_SUCCESS;
        $this->user_id = $userId;
        $this->mobile = $mobile;
        $this->deal_id = $dealId;
        $this->buy_amount = $buyAmount;
        $this->create_time = time();
        $this->update_time = time();
        $this->deal_loan_id = $dealLoanId;
        $this->bid_transfer_id = $bidTransferId;
        try {
            $this->db->startTrans();
            $result = $this->save();
            if(!$result) {
                throw new \Exception("create thirdparty order failed");
            }
            $commitResult = $this->db->commit();
            if(!$commitResult) {
                throw new \Exception("commit thirdparty order failed");
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            if ($this->findBy("`order_id` = '{$orderId}'", "id")) {
                return ThirdpartyOrderModel::ORDER_ALREADY_EXISTED;
            } else {
                return ThirdpartyOrderModel::ORDER_CREATED_FAILED;
            }
        }
        return ThirdpartyOrderModel::ORDER_CREATED_SUCCESS;
    }

    public function getOrderByOrderId($orderId)
    {
        return $this->findBy("`order_id` = '{$orderId}'", '*', array(), true);
    }

    public function getOrderListByTime($siteId, $startTime, $endTime)
    {
        return $this->findAllViaSlave("`site_id` = '{$siteId}' AND `create_time` > '{$startTime}' AND `create_time` < '{$endTime}'");
    }

    public function getOrderByDealLoanId($dealLoanId)
    {
        return $this->findBy("`deal_loan_id` = '{$dealLoanId}'", "*", array(), true);
    }

    public function updateOrderStatus($status, $orderId)
    {
        return $this->updateBy(
            array(
                'order_status' => $status,
                'update_time' => time(),
            ),
            "`order_id` = '{$orderId}'");
    }

    public function updateBidTransferId($orderId, $bidTransferId)
    {
        return $this->updateBy(
            array(
                'bid_transfer_id' => $bidTransferId,
                'update_time' => time(),
            ),
            "`order_id` = '{$orderId}'");
    }

    public function updateRepayTransferId($orderId, $repayTransferId)
    {
        return $this->updateBy(
            array(
                'repay_transfer_id' => $repayTransferId,
                'update_time' => time(),
            ),
            "`order_id` = '{$orderId}'");
    }
}
