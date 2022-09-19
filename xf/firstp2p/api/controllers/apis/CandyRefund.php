<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\Logger;
use core\service\candy\CandyPayService;

/**
 * 信宝退款接口
 */
class CandyRefund extends ApisBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array_merge($this->generalFormRule, [
            'merchantId' => ['filter' => 'string', 'message' => 'merchantId参数错误'],
            'orderId' => ['filter' => 'string', 'message' => 'orderId参数错误'],
            'userId' => ['filter' => 'int', 'message' => 'userId参数错误'],
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

        if ($merchantId !== CandyPayService::LIFE_MERCHANT_ID) {
            throw new \Exception('商户号错误');
        }

        // 退款
        $payService = new CandyPayService();
        $payService->refund($merchantId, $orderId, $userId);

        return $this->echoJson(0, 'success', array(
            'orderId' => $orderId,
        ));
    }

}
