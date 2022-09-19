<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\Logger;
use core\service\candy\CandyPayService;
use core\service\candy\CandyAccountService;

/**
 * 信宝支付扣款接口
 */
class CandyPay extends ApisBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array_merge($this->generalFormRule, [
            'merchantId' => ['filter' => 'string', 'message' => 'merchantId参数错误'],
            'orderId' => ['filter' => 'string', 'message' => 'orderId参数错误'],
            'userId' => ['filter' => 'int', 'message' => 'userId参数错误'],
            'amount' => ['filter' => 'string', 'message' => 'amount参数错误'],
            'desc' => ['filter' => 'string', 'message' => 'desc参数错误'],
        ]);

        if (!$this->form->validate()) {
            return $this->echoJson(10001, $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $merchantId = intval($data['merchantId']);
        $orderId = $data['orderId'];
        $userId = intval($data['userId']);
        $amount = $data['amount'];
        $note = $data['desc'];

        if ($merchantId !== CandyPayService::LIFE_MERCHANT_ID) {
            throw new \Exception('商户号错误');
        }

        $payService = new CandyPayService();

        // 是否已支付过
        $result = $payService->getOrdersInfo($merchantId, array($orderId));
        if (!empty($result)) {
            return $this->echoJson(0, '订单已经支付成功', array(
                'orderId' => $result[0]['out_order_id'],
            ));
        }

        // 余额检查
        $accountService = new CandyAccountService();
        $accountInfo = $accountService->getAccountInfo($userId);
        if ($amount > $accountInfo['amount']) {
            return $this->echoJson(20001, '信宝余额不足');
        }

        // 支付
        $payService = new CandyPayService();
        $payService->pay($merchantId, $orderId, $userId, $amount, $note);

        return $this->echoJson(0, 'success', array(
            'orderId' => $orderId,
        ));
    }

}
