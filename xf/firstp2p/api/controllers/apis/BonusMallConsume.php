<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\Logger;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\service\BonusService;
use core\service\UserService;
use core\dao\BonusConfModel;


class BonusMallConsume extends ApisBaseAction
{

    // private $receiveId = 200002258;

    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array_merge($this->generalFormRule, [
            'userId' => ['filter' => 'int', 'message' => '参数错误'],
            'amount' => ['filter' => 'float', 'message' => '参数错误'],
            'userkey' => ['filter' => 'length', 'option' => ['min' => 1], 'message' => '参数错误'],
            'coins' => ['filter' => 'float', 'message' => '参数错误'],
            'orderId' => ['filter' => 'required', 'option' => ['min' => 1], 'message' => '参数错误'],
            'sn' => ['filter' => 'string', 'option' => ['optional' => true]],
        ]);

        if (!$this->form->validate()) {
            return $this->echoJson(10001, $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        if (!BonusConfModel::get('BONUS_APIS_SWITCH')) {
            return $this->echoJson(10005, '非法访问');
        }

        $this->receiveId = BonusConfModel::get('MALL_ACCOUNT_ID');

        $userId = intval($this->form->data['userId']);
        $money = floatval($this->form->data['amount']);
        $userKey = $this->form->data['userkey'];
        $coins = floatval($this->form->data['coins']);
        $orderId = $this->form->data['orderId'];

        try {

            if (empty($this->receiveId)) throw new \Exception("参数错误", 10001);
            if ($money <= 0) throw new \Exception("参数错误", 10001);
            if ($userId <= 0) throw new \Exception("参数错误", 10001);
            if ($coins <= 0) throw new \Exception("参数错误", 10001);

            $userInfo = (new UserService)->getUserViaSlave($userId);
            if (empty($userInfo)) return $this->echoJson(10002, '用户不存在');


            $bonusSrv = new BonusService;
            $bonusInfo = $bonusSrv->getUsableBonus($userId, true, $money, $orderId);
            $bonusMoney = $bonusInfo['money'];
            if (bccomp($bonusMoney, $money, 2) < 0) throw new \Exception("红包金额不够", 20001);

            $gtm = new GlobalTransactionManager();
            $gtm->setName('bonus_mall');

            // 红包消费
            $gtm->addEvent(new \core\tmevent\bid\BonusMallConsumeEvent($userId, $bonusInfo, $orderId, '商城消费'));

            // 红包商城消费
            $gtm->addEvent(new \core\tmevent\bid\BonusMallTransferEvent($orderId, $userKey, $coins, $userId, $this->receiveId, $bonusInfo));

            if (!$gtm->execute()) {
                throw new \Exception("发送失败");

            }

        } catch (\Exception $e) {
            return $this->echoJson($e->getCode() ?: 10000, $e->getMessage());
        }
        return $this->echoJson(0, 'OK');
    }

}
