<?php
namespace core\service;

use libs\utils\PaymentApi;
use libs\db\Db;
use core\dao\MoneyOrderModel;
use core\dao\TransferOrderModel;
use core\dao\UserModel;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use core\service\UserThirdBalanceService;
use core\dao\FinanceQueueModel;
use core\dao\DealModel;
use core\exception\MoneyOrderException;

/**
 * 交易订单服务
 */
class MoneyOrderService extends BaseService {

    // 设定业务类型
    public $bizType;

    /**
     * 交易类型
     */
    public $changeMoneyDealType = 0;

    // 是否异步处理
    public $changeMoneyAsyn = false;

    /**
     * 付款人资金变动类型(默认扣余额)
     */
    public $payerMoneyType = MoneyOrderEnum::OPTYPE_BALANCE;

    /**
     * 付款人资金变动是否异步
     */
    public $payerChangeMoneyAsyn = false;

    /**
     * 收款人资金变动是否异步
     */
    public $receiverChangeMoneyAsyn = false;

    // 初始化需指定业务类型
    public function __construct($bizType) {
        if (empty($bizType)) {
            throw new \Exception('请指定业务类型');
        }
        $this->bizType = $bizType;
    }

    /**
     * changeUserMoney
     * 第三方业务记订单幂等更改用户余额
     *
     * @param int $bizOrderId 第三方业务订单ID
     * @param int $userId 用户ID
     * @param int $bizSubtype  业务子类型
     * @param int $money 金额
     * @param int $userLogMessage 资金记录标签
     * @param int $userLogNote 资金记录信息
     * @param int $moneyType 资金操作类型
     * @access public
     * @return void
     */
    public function changeUserMoney($bizOrderId, $userId, $bizSubtype, $money, $userLogMessage, $userLogNote, $moneyType = MoneyOrderEnum::OPTYPE_BALANCE) {

        $args = json_encode(func_get_args(), JSON_UNESCAPED_UNICODE);
        Logger::info('BizChangeUserMoney, Start, BizType: ' .$this->bizType. 'Detail:' . $args);
        $orderInfo = MoneyOrderModel::instance()->searchOrder($bizOrderId, $this->bizType, $bizSubtype);
        if (!empty($orderInfo)) {
            Logger::info('BizChangeUserMoney, Order Exists');
            throw new MoneyOrderException('订单已存在', MoneyOrderException::CODE_ORDER_EXIST);
        }
        $user = UserModel::instance()->find($userId, 'id', true);
        if (empty($user)) {
            Logger::info('BizChangeUserMoney用户不存在'. $userId);
            throw new \Exception('BizChangeUserMoney用户不存在'. $userId);
        }

        $GLOBALS['db']->startTrans();
        try {
            $orderData = [
                'biz_order_id' => $bizOrderId,
                'user_id' => $userId,
                'biz_type' => $this->bizType,
                'biz_subtype' => $bizSubtype,
                'amount' => bcmul(abs($money), 100), // 转换为分
            ];
            $res = MoneyOrderModel::instance()->createOrder($orderData);
            if (!$res) {
                throw new \Exception('订单创建失败');
            }
            $bizToken = array('outOrderId' => $bizOrderId);
            $user->changeMoneyAsyn = $this->changeMoneyAsyn;
            $user->changeMoneyDealType = $this->changeMoneyDealType;
            $res = $user->changeMoney($money, $userLogMessage, $userLogNote, get_admin_id(), 0, $moneyType,0,$bizToken);

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Logger::info('BizChangeUserMoney, 订单重复写入');
                throw new MoneyOrderException('订单已存在, 重复插入', MoneyOrderException::CODE_ORDER_EXIST);
            }
            Logger::info('BizChangeUserMoney失败' . $e->getMessage());
            throw new \Exception('BizChangeUserMoney失败' . $e->getMessage());
        }

        Logger::info('BizChangeUserMoney, Success');
        return true;
    }

    /**
     * transfer
     * 转账
     *
     * @param mixed $bizOrderId 业务订单号
     * @param mixed $bizSubtype 子业务类型
     * @param mixed $payerId 付款方ID
     * @param mixed $receiverId 收款方ID
     * @param mixed $money 转账金额
     * @param mixed $transferBizType 转账类型, 详见FinanceQueueModel中PAYQUEUE_BIZTYPE定义
     * @param mixed $payerMessage 付款方资金记录类型
     * @param mixed $payerNote 付款方资金记录备注
     * @param mixed $receiverMessage 收款方资金记录类型
     * @param mixed $receiverNote 收款方资金记录备注
     * @access public
     * @return void
     */
    public function transfer($bizOrderId, $bizSubtype, $payerId, $receiverId, $money, $transferBizType, $payerMessage, $payerNote, $receiverMessage, $receiverNote) {

        $args = json_encode(func_get_args(), JSON_UNESCAPED_UNICODE);
        Logger::info('BizTransfer, Start, BizType: ' .$this->bizType. ',Detail:' . $args);
        if ($payerId === $receiverId) {
            Logger::info('BizTransfer, 转出和转入不能为同一账户');
            throw new \Exception('转入转出不能为同一账户');
        }
        $orderInfo = TransferOrderModel::instance()->searchOrder($bizOrderId, $this->bizType, $bizSubtype);
        if (!empty($orderInfo)) {
            Logger::info('BizTransfer, Order Exists');
            throw new MoneyOrderException('订单已存在', MoneyOrderException::CODE_ORDER_EXIST);
        }

        $payer = UserModel::instance()->find($payerId, 'id', true);
        if (empty($payer)) {
            Logger::info('BizTransfer, Order Exists, BizType');
            throw new \Exception('BizTransfer付款用户不存在'. $payerId);
        }
        $receiver = UserModel::instance()->find($receiverId, 'id', true);
        if (empty($receiver)) {
            Logger::info('BizTransfer收款用户不存在');
            throw new \Exception('BizTransfer收款用户不存在'. $receiverId);
        }

        if ($money <= 0) {
            Logger::info('BizTransfer转账金额必须大于0');
            throw new \Exception('BizTransfer转账金额必须大于0');
        }
        $payerMoney = $this->payerMoneyType === MoneyOrderEnum::OPTYPE_FREEZE_DECREASE ? $money : -$money;
        $GLOBALS['db']->startTrans();
        try {
            $orderData = [
                'biz_order_id' => $bizOrderId,
                'biz_type' => $this->bizType,
                'biz_subtype' => $bizSubtype,
                'payer_id' => $payerId,
                'receiver_id' => $receiverId,
                'amount' => bcmul($money, 100), // 转换为分
            ];
            $res = TransferOrderModel::instance()->createOrder($orderData);
            if (!$res) {
                throw new \Exception('订单创建失败');
            }
            $bizToken = array('outOrderId'=>$bizOrderId);
            $payer->changeMoneyAsyn = $this->payerChangeMoneyAsyn;
            $payer->changeMoneyDealType = $this->changeMoneyDealType;
            $payer->changeMoney($payerMoney, $payerMessage, $payerNote, get_admin_id(), 0, $this->payerMoneyType,0,$bizToken);
            $receiver->changeMoneyAsyn = $this->receiverChangeMoneyAsyn;
            $receiver->changeMoneyDealType = $this->changeMoneyDealType;
            $receiver->changeMoney($money, $receiverMessage, $receiverNote, get_admin_id(), 0, MoneyOrderEnum::OPTYPE_BALANCE,0,$bizToken);

            // 存管标的不发送同步请求
            if ($this->changeMoneyDealType != DealModel::DEAL_TYPE_SUPERVISION) {
                $this->sync($payer->id, $receiver->id, $money, $transferBizType);
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Logger::info('BizTransfer, 订单重复插入');
                throw new MoneyOrderException('订单已存在, 重复插入', MoneyOrderException::CODE_ORDER_EXIST);
            }
            Logger::error('BizTransfer转账失败:'.$e->getMessage());
            throw new \Exception('BizTransfer转账失败:'.$e->getMessage());
        }
        Logger::info('BizTransfer, Success');
        return true;
    }

    public function sync($payerId, $receiverId, $amount, $transferBizType) {
        // 同步到支付
        $data = array(
            'outOrderId' => '',
            'payerId' => $payerId,
            'receiverId' => $receiverId,
            'repaymentAmount' => bcmul($amount, 100),
            'curType' => 'CNY',
            'bizType' => $transferBizType,
            'batchId' => '',
        );

        $res = FinanceQueueModel::instance()->push(array('orders' => array($data)), 'transfer');
        if (!$res) {
            throw new \Exception('插入转账队列失败, res:'.json_encode($res, JSON_UNESCAPED_UNICODE));
        }
    }
}
