<?php
/**
 * 网信生活-网信出行-调用收银台创建订单
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\UserTripService;

class PaymentCreateOrder extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'merchantId' => array('filter' => 'required', 'message' => 'merchantId is required'),
            'outOrderId' => array('filter' => 'required', 'message' => 'outOrderId is required'),
            'amount' => array('filter' => 'required', 'message' => 'amount is required'),
            'goodsName' => array('filter' => 'string'),
            'cardId' => array('filter' => 'int'), // 银行绑卡自增ID
            'goodsDesc' => array('filter' => 'string'), // 商品描述
            'shouldAmount' => array('filter' => 'int'), // 应付金额
            'discountAmount' => array('filter' => 'int'), // 优惠金额
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        try {
            $userInfo = $this->getUserByToken();
            if (empty($userInfo)) {
                throw new \Exception('ERR_GET_USER_FAIL');
            }

            $data = $this->form->data;
            // 用户ID
            $data['userId'] = $userInfo['id'];
            $obj = new UserTripService();
            $response = $obj->createBusinessOrder($data);
            if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
                throw new \Exception($response['errorMsg'], $response['errorCode']);
            }

            $this->json_data = $response['data'];
        } catch (\Exception $e) {
            if ($e->getCode() == 0) {
                $this->setErr($e->getMessage());
            } else {
                $this->setErr('ERR_MANUAL_REASON', $e->getMessage());
                $this->errno = $e->getCode();
            }
            return false;
        }
    }
}