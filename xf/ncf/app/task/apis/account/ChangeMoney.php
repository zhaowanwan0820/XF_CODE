<?php
/**
 * 资金账户变动
 */
namespace task\apis\account;

use libs\db\Db;
use libs\utils\Logger;
use task\lib\ApiAction;
use core\service\account\AccountService;
use NCFGroup\Common\Library\Idemportent;
use core\enum\IdemportentEnum;

class ChangeMoney extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $orderId = !empty($param['orderId']) ? (int) $param['orderId'] : 0;
        $accountId = !empty($param['accountId']) ? (int) $param['accountId'] : 0;
        $money = !empty($param['money']) ? (int) $param['money'] : 0; //单位分
        $message = !empty($param['message']) ? $param['message'] : '';
        $note = !empty($param['note']) ? $param['note'] : '';
        $moneyType = !empty($param['moneyType']) ? (int)$param['moneyType'] : 0;
        $isAsync = !empty($param['isAsync']) ? (int)$param['isAsync'] : 0; //是否异步操作
        $adminId = !empty($param['adminId']) ? (int)$param['adminId'] : 0;

        if (empty($orderId) || empty($accountId) || empty($money) || empty($message)) {
            Logger::error(sprintf('apis ChangeMoney. params is error'));
            return false;
        }

        try {
            //检查幂等
            $db = Db::getInstance('firstp2p');
            $db->startTrans();
            $IdemportentType = IdemportentEnum::TYPE_MONEY_PREFIX . $accountId . '_' . $moneyType;
            $res = Idemportent::set($db->link_id, $IdemportentType, $orderId, IdemportentEnum::STATUS_MONEY);
            if ($res === Idemportent::EXISTS) {
                $db->commit();
                Logger::info(sprintf('apis ChangeMoney. Order already exists. orderId:%s', $orderId));
                $this->json_data = ['status' => '00', 'msg' => '成功'];
                return true;
            }

            //资金变动
            $money = bcdiv($money, 100, 2); //转成元
            $bizToken = ['orderId' => $orderId];
            AccountService::changeMoney($accountId, $money, $message, $note, $moneyType, $isAsync, true, $adminId, $bizToken);
            $db->commit();

            $this->json_data = ['status' => '00', 'msg' => '成功'];
            Logger::info(sprintf('apis ChangeMoney. success. orderId:%s', $orderId));
            return true;
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            Logger::info(sprintf('apis ChangeMoney. error:%s', $e->getMessage()));
            $this->json_data = ['status' => '01', 'msg' => $e->getMessage()];
            return false;
        }
    }
}
