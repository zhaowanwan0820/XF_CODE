<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\Logger;
use core\service\WXBonusService;
use core\service\UserService;
use core\dao\BonusConfModel;

class BonusMallAcquire extends ApisBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');

        $this->form->rules = array_merge($this->generalFormRule, [
            'userId' => ['filter' => 'int', 'message' => '参数错误'],
            'sn' => ['filter' => 'int', 'message' => '参数错误', 'option' => ['optional' => true]],
            'amount' => ['filter' => 'float', 'message' => '参数错误', 'option' => ['optional' => true]],
            'expireDay' => ['filter' => 'int', 'message' => '参数错误', 'option' => ['optional' => true]],
            'orderId' => ['filter' => 'length', 'option' => ['min' => 1], 'message' => '参数错误'],
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

        $data = $this->form->data;
        $ruleId = intval($data['sn']);
        $userId = intval($data['userId']);
        $money = floatval($data['amount']);
        $expireDay = intval($data['expireDay']);
        $orderId = $data['orderId'];
        $accountId = BonusConfModel::get('MALL_ACCOUNT_ID');

        if (empty($accountId)) return $this->echoJson(10001, '参数错误');
        if ($userId <= 0) return $this->echoJson(10001, '参数错误');

        $userInfo = (new UserService)->getUserViaSlave($userId);
        if (empty($userInfo)) return $this->echoJson(10002, '用户不存在');

        if ($ruleId > 0) {
            $res = (new WXBonusService)->acquireRule($ruleId, $userId, '', $orderId);
        } else if ($money > 0 && $expireDay > 0) {
            $res = (new WXBonusService)->acquireMall($userId, $money, $expireDay, $orderId, $accountId);
        } else {
            return $this->echoJson(10001, '参数错误');
        }
        if ($res) return $this->echoJson(0, 'OK');
        else return $this->echoJson(20001, '发红包失败');
    }

}
