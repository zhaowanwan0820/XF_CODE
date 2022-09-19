<?php
/**
 * @desc 投资逻辑 投资不存在回滚
 * Date: 2017-02-23 17:13
 */

namespace core\tmevent\bid;

use core\service\P2pIdempotentService;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use NCFGroup\Protos\Bonus\Enum\MoneyOrderEnum as BonusMoneyOrderEnum;
use core\dao\DealModel;
use libs\utils\Logger;
use api\controllers\apis\ApisBaseAction as SignTool;
use libs\utils\Curl;
use NCFGroup\Ptp\services\PtpMoneyOrderService;
use core\dao\BonusConfModel;

class BonusMallTransferEvent extends GlobalTransactionEvent {

    public function __construct($orderId, $userKey, $coins, $userId, $receiveId, $bonusInfo)
    {
        $this->orderId = $orderId;
        $this->userId = $userId;
        $this->userKey = $userKey;
        $this->coins = $coins;
        $this->receiveId = $receiveId;
        $this->money = $bonusInfo['money'];
        $this->accountInfo = $bonusInfo['accountInfo'];
    }

    /**
     * 库存扣减、标的状态更新、订单状态修改
     */
    public function execute()
    {

        $GLOBALS['db']->startTrans();
        try {

            // 给用户转账，红包充值
            foreach ($this->accountInfo as $item) {
                $this->transfer(BonusMoneyOrderEnum::RECHARGE, $item['rpUserId'], $this->userId, $item['rpAmount'],
                        '红包充值', "{$this->userId}使用红包充值于商城消费",
                        '使用红包充值', "使用红包充值于商城消费");
            }
            Logger::info(implode('|', [__METHOD__, "给用户转账成功"]));

            // 消费转账
            $this->transfer(BonusMoneyOrderEnum::MALL_GOLD_COIN, $this->userId, $this->receiveId, $this->money,
                        '使用红包消费', "使用红包消费于商城",
                        '红包消费', "{$this->userId}使用红包消费于商城");
            Logger::info(implode('|', [__METHOD__, "给商城转账成功"]));


            $this->mallCallback();
            Logger::info(implode('|', [__METHOD__, "商城Callback成功"]));

            $GLOBALS['db']->commit();

        }catch (\Exception $e){

            $GLOBALS['db']->rollback();

            Logger::info(implode('|', [__METHOD__, 'Exception', $e->getMessage()]));
            return false;
        }
        return true;
    }


    private function transfer($subType, $payerId, $receiveId, $money, $pMsg, $pNote, $rMsg, $rNote)
    {

        $req = new \NCFGroup\Protos\Ptp\RequestMoneyTransfer();
        $req->setBizOrderId(intval($this->orderId));
        $req->setBizSubtype($subType);
        $req->setBizType(MoneyOrderEnum::BIZ_TYPE_BONUS);
        $req->setPayerId(intval($payerId));
        $req->setReceiverId(intval($receiveId));
        $req->setAmount(intval(bcmul($money, 100, 2)));
        $req->setTransferBizType(\NCFGroup\Protos\Ptp\Enum\PayQueueEnum::BIZTYPE_BONUS);
        $req->setPayerMessage($pMsg);
        $req->setPayerNote($pNote);
        $req->setReceiverMessage($rMsg);
        $req->setReceiverNote($rNote);
        $req->setChangeMoneyDealType(\NCFGroup\Protos\Ptp\Enum\DealEnum::DEAL_TYPE_MALL);

        $rsp = (new PtpMoneyOrderService)->transfer($req);
        Logger::info(implode('|', [__METHOD__, json_encode($rsp, JSON_UNESCAPED_UNICODE)]));
        if ($rsp->resCode) {
            throw new \Exception($rsp->errorMsg, $rsp->errorCode);
        }
        return true;
    }

    private function mallCallback()
    {
        $params = [
            'userId' => $this->userId,
            'userkey' => $this->userKey,
            'gold_coin' => $this->coins,
            'type' => 0, // 0 充值 1 扣除
            'buy_type' => 1,
            'orderId' => $this->orderId,
            'timestamp' => SignTool::getTimestamp(),
        ];
        $params['sign'] = SignTool::sign($params, SignTool::SALT_MALL);
        $api = app_conf('MALL_COIN_ACQUIRE');
        $api = BonusConfModel::get('BONUS_APIS_MALL_COIN');
        if (empty($api)) throw new \Exception("无效接口");

        $res = Curl::post($api, $params);
        Logger::info(implode('|', [__METHOD__, $api, json_encode($params, JSON_UNESCAPED_UNICODE), $res]));
        $res = json_decode($res, true);
        if ($res['result']) return true;
        throw new \Exception("发金币失败");

    }
}
