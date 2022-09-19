<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/8/5
 * Time: 17:03
 */

namespace core\service;
use core\dao\ThirdpartyOrderModel;

class ThirdpartyOrderService extends BaseService{

    public function getDetailInfo($orderId, $orderStatus) {
        $result = '';
        if($orderStatus == ThirdpartyOrderModel::ORDER_ALREADY_EXISTED) {
            $result = "订单{$orderId}已经存在！";
        } else if($orderStatus == ThirdpartyOrderModel::ORDER_CREATED_SUCCESS) {
            $result = "订单{$orderId}处理成功！";
        } else if($orderStatus == ThirdpartyOrderModel::ORDER_CREATED_FAILED) {
            $result = "订单{$orderId}处理失败！";
        }
        return $result;
    }

    public function createOrderRecord($siteId, $orderId, $userId, $mobile, $dealId, $buyAmount, $dealLoadId, $bidTransferId) {
        return ThirdpartyOrderModel::instance()->createOrderRecord($siteId, $orderId, $userId, $mobile, $dealId, $buyAmount, $dealLoadId, $bidTransferId);
    }

    public function getOrderByOrderId($orderId) {
        $order = ThirdpartyOrderModel::instance()->getOrderByOrderId($orderId);
        if(empty($order)) {
            return array(
                'errno' =>  -1,
                'errmsg' => "无法找到该订单",
                'data' => '',
            );
        }
        return array(
            'errno' => 0,
            'errmsg'    => '',
            'data' => array(
                'order_id' => $order['order_id'],
                'order_status' => $order['order_status'],
                'deal_id' => $order['deal_id'],
                'money' => $order['buy_amount'],
                'mobile' => $order['mobile'],
                'user_id'   =>  $order['user_id'],
                'create_time' => $order['create_time'],
                'deal_loan_id'  =>  $order['deal_loan_id'],
            ),
        );
    }

    public function updateOrderStatus($status, $orderId) {
        return ThirdpartyOrderModel::instance()->updateOrderStatus($status, $orderId);
    }

    public function updateDealLoanIdAndStatus($dealLoanId, $status, $orderId) {
        return ThirdpartyOrderModel::instance()->updateDealLoanIdAndStatus($dealLoanId, $status, $orderId);
    }

    public function updateBidTransferId($orderId, $bidTransferId) {
        return ThirdpartyOrderModel::instance()->updateBidTransferId($orderId, $bidTransferId);
    }

    public function updateRepayTransferId($orderId, $repayTransferId) {
        return ThirdpartyOrderModel::instance()->updateRepayTransferId($orderId, $repayTransferId);
    }
}