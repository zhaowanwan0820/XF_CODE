<?php
/**
 * 股票配资资金接口Service
 */
namespace core\service;

use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\TransferService;
use core\dao\UserModel;
use core\dao\StockLockModel;

class StockService extends BaseService
{

    /**
     * 冻结资金
     */
    public function lock($outOrderId, $userId, $amount, $note)
    {
        $outOrderId = addslashes($outOrderId);
        $userId = addslashes($userId);
        $note = addslashes($note);
        $amount = $this->_filterAmount($amount);
        $orderInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_stock_lock WHERE out_order_id='{$outOrderId}' AND user_id ='{$userId}'");
        if (!empty($orderInfo)) {
            if ($orderInfo['status'] == StockLockModel::STATUS_LOCK) {
                return true;
            }

            throw new \Exception('订单已终态');
        }
        $GLOBALS['db']->startTrans();
        try{
            $data = array(
                'out_order_id' => $outOrderId,
                'user_id' => $userId,
                'amount' => $amount,
                'status' => StockLockModel::STATUS_LOCK,
                'create_time' => time(),
            );
            if (!$GLOBALS['db']->insert('firstp2p_stock_lock', $data)) {
                throw new \Exception('落单失败');
            }
            $user = UserModel::instance()->find($userId);
            if (empty($user)) {
                throw new \Exception('用户不存在');
            }
            $money = bcdiv($amount, 100, 2);
            $result = $user->changeMoney($money, '配置冻结', $note, 0, 0, UserModel::TYPE_LOCK_MONEY, 0);
            if (!$result) {
                throw new \Exception('用户余额冻结失败');
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 扣减冻结
     */
    public function pay($outOrderId, $receiverId, $payerNote, $receiverNote)
    {
        //查询订单
        $outOrderId = addslashes($outOrderId);
        $orderInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_stock_lock WHERE out_order_id='{$outOrderId}'");
        if (empty($orderInfo)) {
            throw new \Exception('订单不存在');
        }

        if ($orderInfo['status'] == StockLockModel::STATUS_PAYED) {
            return true;
        }

        if ($orderInfo['status'] == StockLockModel::STATUS_UNLOCK) {
            throw new \Exception('订单已解冻');
        }

        try {
            $GLOBALS['db']->startTrans();

            //修改订单状态
            $data = array(
                'out_order_id' => $outOrderId,
                'receiver_id' => $receiverId,
                'status' => StockLockModel::STATUS_PAYED,
                'update_time' => time(),
            );
            $ret = $GLOBALS['db']->update('firstp2p_stock_lock', $data, "id='{$orderInfo['id']}' AND status=".StockLockModel::STATUS_LOCK);
            if (!$ret || $GLOBALS['db']->affected_rows() < 1) {
                throw new \Exception('修改订单状态失败');
            }

            //转账到商户
            $transferAmount = bcdiv($orderInfo['amount'], 100, 2);
            $transferService = new TransferService();
            $transferService->payerMoneyType = UserModel::TYPE_DEDUCT_LOCK_MONEY;
            $transferService->transferById($orderInfo['user_id'], $receiverId, $transferAmount, '配资扣款', $payerNote, '配资付款', $receiverNote, 'STOCKPAY|'.$outOrderId);

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 解冻
     */
    public function unlock($outOrderId, $note)
    {
        $outOrderId = addslashes($outOrderId);
        $note = addslashes($note);
        $orderInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_stock_lock WHERE out_order_id='{$outOrderId}'");
        if (empty($orderInfo)) {
            throw new \Exception('无效的订单');
        }

        if ($orderInfo['status'] == StockLockModel::STATUS_UNLOCK) {
            return true;
        }

        if ($orderInfo['status'] == StockLockModel::STATUS_PAYED) {
            throw new \Exception('订单已扣款');
        }

        $GLOBALS['db']->startTrans();
        try{
            $data = array(
                'status' => StockLockModel::STATUS_UNLOCK,
                'update_time' => time(),
            );
            $res = $GLOBALS['db']->update('firstp2p_stock_lock', $data, "id = {$orderInfo['id']} AND status = " . StockLockModel::STATUS_LOCK);
            if (!$res || $GLOBALS['db']->affected_rows() <= 0) {
                throw new \Exception('更新订单失败');
            }

            $user = UserModel::instance()->find($orderInfo['user_id']);
            if (empty($user)) {
                throw new \Exception('用户不存在');
            }
            $money = bcdiv($orderInfo['amount'], 100, 2);
            $result = $user->changeMoney(-$money, '配资解冻', $note, 0, 0, UserModel::TYPE_LOCK_MONEY, 0);
            if (!$result) {
                throw new \Exception('用户余额解冻失败');
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 查询冻结
     */
    public function query($outOrderId)
    {
        $outOrderId = addslashes($outOrderId);
        $orderInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_stock_lock WHERE out_order_id = '$outOrderId'");
        return $orderInfo;
    }

    /**
     * 转账
     */
    public function transfer($outOrderId, $payerId, $receiverId, $amount, $payerNote, $receiverNote)
    {
        $outOrderId = addslashes($outOrderId);
        $amount = $this->_filterAmount($amount);
        $orderInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_stock_transfer WHERE out_order_id='{$outOrderId}'");
        if (!empty($orderInfo)) {
            return true;
        }

        try {
            $GLOBALS['db']->startTrans();

            //落单
            $data = array(
                'out_order_id' => $outOrderId,
                'payer_id' => $payerId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'create_time' => time(),
            );
            if (!$GLOBALS['db']->insert('firstp2p_stock_transfer', $data)) {
                throw new Exception('落单失败');
            }

            //转账
            $transferService = new TransferService();
            $transferService->payerNegative = false;
            $transferService->receiverNegative = false;
            $transferAmount = bcdiv($amount, 100, 2);
            $transferService->transferById($payerId, $receiverId, $transferAmount, '配资转出', $payerNote, '配资转入', $receiverNote, 'STOCK|'.$outOrderId);

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return true;
    }

    private function _filterAmount($amount) {
        if ($amount <= 0 || intval($amount) != $amount) {
            throw new \Exception('金额必须为正整数');
        }

        return intval($amount);
    }
}
