<?php
/**
 * 按账户转账
 */
namespace task\apis\account;

use libs\db\Db;
use libs\utils\Logger;
use task\lib\ApiAction;
use core\service\account\AccountService;
use NCFGroup\Common\Library\Idemportent;
use core\enum\IdemportentEnum;
use core\enum\AccountEnum;

class TransferMoney extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $orderId = !empty($param['orderId']) ? (int) $param['orderId'] : 0; //订单号
        $payerId = !empty($param['payerId']) ? (int) $param['payerId'] : 0; //付款人id
        $receiverId = !empty($param['receiverId']) ? (int) $param['receiverId'] : 0; //收款人id
        $money = !empty($param['money']) ? (int) $param['money'] : 0; //单位分
        $payerType = !empty($param['payerType']) ? $param['payerType'] : ''; //付款类型, log_info
        $payerNote = !empty($param['payerNote']) ? $param['payerNote'] : ''; //付款备注
        $receiverType = !empty($param['receiverType']) ? $param['receiverType'] : ''; //收款类型, log_info
        $receiverNote = !empty($param['receiverNote']) ? $param['receiverNote'] : ''; //收款备注
        $payerAsync = !empty($param['payerAsync']) ? (int)$param['payerAsync'] : 0; //是否异步操作
        $receiverAsync = !empty($param['receiverAsync']) ? (int)$param['receiverAsync'] : 0; //是否异步操作
        $payerMoneyType = !empty($param['payerMoneyType']) ? (int)$param['payerMoneyType'] : AccountEnum::MONEY_TYPE_REDUCE; //付款人资金操作类型，默认扣减余额
        $receiverMoneyType = !empty($param['receiverMoneyType']) ? (int)$param['receiverMoneyType'] : AccountEnum::MONEY_TYPE_INCR; //收款人资金操作类型，默认增加金额

        if (empty($orderId) || empty($payerId) || empty($receiverId) || empty($money)) {
            Logger::error(sprintf('apis TransferMoney. params is error'));
            return false;
        }

        try {
            //检查幂等
            $db = Db::getInstance('firstp2p');
            $db->startTrans();
            $IdemportentType = IdemportentEnum::TYPE_MONEY_PREFIX . $payerId . '_' . $receiverId;
            $res = Idemportent::set($db->link_id, $IdemportentType, $orderId, IdemportentEnum::STATUS_MONEY);
            if ($res === Idemportent::EXISTS) {
                $db->commit();
                Logger::info(sprintf('apis TransferMoney. Order already exists. orderId:%s', $orderId));
                $this->json_data = ['status' => '00', 'msg' => '成功'];
                return true;
            }

            //按账户转账
            $money = bcdiv($money, 100, 2); //转成元
            $payerBizToken = $receiverBizToken =  ['orderId' => $orderId];
            AccountService::transferMoney($payerId, $receiverId, $money, $payerType, $payerNote, $receiverType, $receiverNote, $payerAsync, $receiverAsync, $payerBizToken, $receiverBizToken, $payerMoneyType, $receiverMoneyType);
            $db->commit();

            $this->json_data = ['status' => '00', 'msg' => '成功'];
            Logger::info(sprintf('apis TransferMoney. success. orderId:%s', $orderId));
            return true;
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            Logger::info(sprintf('apis TransferMoney. error:%s', $e->getMessage()));
            $this->json_data = ['status' => '01', 'msg' => $e->getMessage()];
            return false;
        }
    }
}
