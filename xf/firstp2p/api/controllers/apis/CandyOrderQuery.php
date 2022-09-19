<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\Logger;
use core\service\candy\CandyPayService;

/**
 * 信宝支付订单查询接口
 */
class CandyOrderQuery extends ApisBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array_merge($this->generalFormRule, [
            'merchantId' => ['filter' => 'string', 'message' => 'merchantId参数错误'],
            'orderIds' => ['filter' => 'string', 'message' => 'orderIds参数错误'],
        ]);

        if (!$this->form->validate()) {
            return $this->echoJson(10001, $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $merchantId = intval($data['merchantId']);
        $orderIds = $data['orderIds'];

        if ($merchantId !== CandyPayService::LIFE_MERCHANT_ID) {
            throw new \Exception('商户号错误');
        }

        $ids = explode(',', $orderIds);
        if (count($ids) > 100) {
            throw new \Exception('每次最多查询100条');
        }

        $payService = new CandyPayService();
        $result = $payService->getOrdersInfo($merchantId, $ids);

        $orders = array();
        foreach ($result as $item) {
            $orders[] = array(
                'orderId' => $item['out_order_id'],
                'userId' => $item['user_id'],
                'amount' => $item['amount'],
                'status' => $item['status'],
            );
        }

        return $this->echoJson(0, 'success', array(
            'orders' => $orders,
        ));
    }

}
